<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Browser coverage for the public auth pages: server validation errors must
 * actually render in the UI, and the pages must stay accessible at the
 * strictest axe level (the palette is tuned for WCAG AA text contrast).
 */
it('shows an error when the login credentials do not match', function (): void {
    visit('/login')
        ->fill('#email', 'unknown@example.com')
        ->fill('#password', 'wrong-password')
        ->press('Se connecter')
        ->assertPathIs('/login')
        ->assertSee('Ces identifiants ne correspondent pas à nos enregistrements.');
});

it('keeps the login page accessible while showing validation errors', function (): void {
    visit('/login')
        ->fill('#email', 'unknown@example.com')
        ->fill('#password', 'wrong-password')
        ->press('Se connecter')
        ->assertSee('Ces identifiants ne correspondent pas à nos enregistrements.')
        ->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the login, register, and forgot password pages', function (string $path): void {
    $page = visit($path);
    settleForAccessibilityScan($page);

    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
})->with([
    'login' => '/login',
    'register' => '/register',
    'forgot-password' => '/forgot-password',
    'reset-password' => '/reset-password/fake-token',
]);

it('blocks an empty login submission client-side', function (): void {
    visit('/login')
        ->press('Se connecter')
        ->assertPathIs('/login')
        ->assertDontSee('Ces identifiants ne correspondent pas à nos enregistrements.');
});

it('shows an error when the register password confirmation does not match', function (): void {
    visit('/register')
        ->fill('#name', 'Jean Tremblay')
        ->fill('#organization', 'Tremblay Déneigement')
        ->fill('#email', 'jean@example.com')
        ->fill('#password', 'super-secret-password')
        ->fill('#password_confirmation', 'different-password')
        ->press('Créer le compte')
        ->assertPathIs('/register')
        ->assertSee('La confirmation du champ mot de passe ne correspond pas.');
});

it('shows an error when registering with an existing email', function (): void {
    $user = User::factory()->create(['email' => 'taken@example.com']);

    visit('/register')
        ->fill('#name', 'Jean Tremblay')
        ->fill('#organization', 'Tremblay Déneigement')
        ->fill('#email', $user->email)
        ->fill('#password', 'super-secret-password')
        ->fill('#password_confirmation', 'super-secret-password')
        ->press('Créer le compte')
        ->assertPathIs('/register')
        ->assertSee('La valeur du champ adresse courriel est déjà utilisée.');
});

it('blocks a non-email address client-side on forgot password', function (): void {
    // The email input has type="email" and the form is not noValidate, so the
    // browser's native validation blocks the submission...
    visit('/forgot-password')
        ->fill('#email', 'not-an-email')
        ->press('Envoyer le lien de réinitialisation par courriel')
        ->assertPathIs('/forgot-password')
        ->assertDontSee('Le champ adresse courriel doit être une adresse courriel valide.');
});

it('shows the reset link status for an unknown email', function (): void {
    visit('/forgot-password')
        ->fill('#email', 'unknown@example.com')
        ->press('Envoyer le lien de réinitialisation par courriel')
        ->assertSeeIn('@forgot-password-status', 'Un lien de réinitialisation sera envoyé si le compte existe.');
});

it('shows an error when the login challenge code is invalid', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->press('Se connecter')
        ->assertPathIs('/login-challenge')
        ->fill('#code', '654321')
        ->assertSee('Le code fourni est invalide ou a expiré.')
        ->assertPathIs('/login-challenge');
});

it('keeps the confirm button disabled until the code is complete', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->press('Se connecter')
        ->assertPathIs('/login-challenge');

    $page->assertButtonDisabled('@confirm-login-button');

    $page->fill('#code', '12345')
        ->assertButtonDisabled('@confirm-login-button');

    $page->fill('#code', '123456')
        ->assertButtonEnabled('@confirm-login-button');
});

it('shows an error when the confirmed password is wrong', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = loginViaUi($user);

    $page->navigate('/user/confirm-password');
    $page->wait(2);

    $page->fill('#password', 'wrong-password')
        ->press('Confirmer le mot de passe')
        ->assertSee('Le mot de passe fourni est incorrect.')
        ->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the login challenge page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->press('Se connecter')
        ->assertPathIs('/login-challenge');

    settleForAccessibilityScan($page);

    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the reset password page', function (): void {
    $page = visit('/reset-password/fake-token');

    settleForAccessibilityScan($page);

    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});
