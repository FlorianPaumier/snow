<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser coverage for the self-service settings pages (password, profile,
 * delete account) and the dashboard filters: server validation errors must
 * render and the pages must stay accessible.
 */
it('shows an error when the current password is wrong', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    $page->navigate('/settings/password');
    $page->wait(2);

    $page->fill('#current_password', 'wrong-password')
        ->fill('#password', 'brand-new-password')
        ->fill('#password_confirmation', 'brand-new-password')
        ->press('Enregistrer');

    $page->assertSee('Le mot de passe fourni est incorrect.')
        ->assertPathIs('/settings/password');
});

it('shows an error when the new password confirmation does not match', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    $page->navigate('/settings/password');
    $page->wait(2);

    $page->fill('#current_password', 'password')
        ->fill('#password', 'brand-new-password')
        ->fill('#password_confirmation', 'different-password')
        ->press('Enregistrer');

    $page->assertSee('La confirmation du champ mot de passe ne correspond pas.')
        ->assertPathIs('/settings/password');
});

it('shows an error when the profile email is already taken', function (): void {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);
    $page = loginViaUi($user);

    $page->navigate('/settings/profile');
    $page->wait(2);

    $page->fill('#email', 'taken@example.com')
        ->press('Enregistrer');

    $page->assertSee('La valeur du champ adresse courriel est déjà utilisée.')
        ->assertPathIs('/settings/profile');
});

it('shows an error for a wrong password in the delete account dialog', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    $page->navigate('/settings/profile');
    $page->wait(2);

    // Open the delete-account confirmation dialog...
    $page->click('@delete-user-button');
    $page->wait(1);

    $page->fill('#password', 'wrong-password')
        ->click('@confirm-delete-user-button');

    $page->assertSee('Le mot de passe fourni est incorrect.');

    // Cancelling clears the errors for the next attempt...
    $page->click('Annuler');
    $page->wait(1);

    $page->click('@delete-user-button');
    $page->wait(1);
    $page->assertDontSee('Le mot de passe fourni est incorrect.');
});

it('is accessible on the settings and dashboard pages', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/settings/profile');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/settings/password');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/settings/appearance');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the profile page with the delete dialog open', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    $page->navigate('/settings/profile');
    $page->wait(2);
    settleForAccessibilityScan($page);

    $page->click('@delete-user-button');
    $page->wait(1);

    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('redirects an invalid season filter back to the dashboard', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    // Reuse the logged-in page (a fresh visit() would lose the session)...
    $page->navigate('/dashboard?season=not-a-season');
    $page->wait(2);
    settleForAccessibilityScan($page);

    $page->assertPathIs('/dashboard')
        ->assertSee('Tableau de bord')
        ->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('redirects an invalid slot filter back to the dashboard', function (): void {
    $user = User::factory()->create();
    $page = loginViaUi($user);

    $page->navigate('/dashboard?slot=midsummer');
    $page->wait(2);

    $page->assertPathIs('/dashboard')
        ->assertSee('Tableau de bord');
});
