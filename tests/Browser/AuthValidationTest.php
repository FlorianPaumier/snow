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
        ->press('Log in')
        ->assertPathIs('/login')
        ->assertSee('These credentials do not match our records.');
});

it('keeps the login page accessible while showing validation errors', function (): void {
    visit('/login')
        ->fill('#email', 'unknown@example.com')
        ->fill('#password', 'wrong-password')
        ->press('Log in')
        ->assertSee('These credentials do not match our records.')
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
        ->press('Log in')
        ->assertPathIs('/login')
        ->assertDontSee('These credentials do not match our records.');
});

it('shows an error when the register password confirmation does not match', function (): void {
    visit('/register')
        ->fill('#name', 'Jean Tremblay')
        ->fill('#organization', 'Tremblay Déneigement')
        ->fill('#email', 'jean@example.com')
        ->fill('#password', 'super-secret-password')
        ->fill('#password_confirmation', 'different-password')
        ->press('Create account')
        ->assertPathIs('/register')
        ->assertSee('The password field confirmation does not match.');
});

it('shows an error when registering with an existing email', function (): void {
    $user = User::factory()->create(['email' => 'taken@example.com']);

    visit('/register')
        ->fill('#name', 'Jean Tremblay')
        ->fill('#organization', 'Tremblay Déneigement')
        ->fill('#email', $user->email)
        ->fill('#password', 'super-secret-password')
        ->fill('#password_confirmation', 'super-secret-password')
        ->press('Create account')
        ->assertPathIs('/register')
        ->assertSee('The email has already been taken.');
});

it('blocks a non-email address client-side on forgot password', function (): void {
    // The email input has type="email" and the form is not noValidate, so the
    // browser's native validation blocks the submission...
    visit('/forgot-password')
        ->fill('#email', 'not-an-email')
        ->press('Email password reset link')
        ->assertPathIs('/forgot-password')
        ->assertDontSee('The email field must be a valid email address.');
});

it('shows the reset link status for an unknown email', function (): void {
    visit('/forgot-password')
        ->fill('#email', 'unknown@example.com')
        ->press('Email password reset link')
        ->assertSeeIn('@forgot-password-status', 'A reset link will be sent if the account exists.');
});

it('shows an error when the login challenge code is invalid', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->press('Log in')
        ->assertPathIs('/login-challenge')
        ->fill('#code', '654321')
        ->assertSee('The provided code is invalid or has expired.')
        ->assertPathIs('/login-challenge');
});

it('keeps the confirm button disabled until the code is complete', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->press('Log in')
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
        ->press('Confirm password')
        ->assertSee('The provided password was incorrect.')
        ->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the login challenge page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $page = visit('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->press('Log in')
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
