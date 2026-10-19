<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Password;

/**
 * Browser coverage for the admin user management pages: server validation
 * errors must render on the create/edit forms and the pages stay accessible.
 * Staff onboarding no longer collects a password in the admin form — the
 * member sets it when accepting their invitation — so password validation
 * is covered on the invitation page.
 */
it('shows an error when the invitation password confirmation does not match', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->pendingInvitation()->forOrganization($organization)->create();
    $token = (string) Password::broker('invitations')->createToken($member);

    $page = visit('/accept-invitation/'.$token.'?email='.$member->email);
    $page->wait(2);

    $page->fill('#password', 'super-secret-password')
        ->fill('#password_confirmation', 'different-password')
        ->press('Définir le mot de passe');

    $page->assertSee('La confirmation du champ mot de passe ne correspond pas.')
        ->assertPathIs('/accept-invitation/'.$token);
});

it('shows an error for a duplicate email', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->forOrganization($admin->organization)->create(['email' => 'taken@example.com']);
    $page = loginViaUi($admin);

    $page->navigate('/users/create');
    $page->wait(2);

    $page->fill('#name', 'Jean Tremblay')
        ->fill('#email', 'taken@example.com')
        ->press("Créer l'utilisateur");

    $page->assertSee('La valeur du champ adresse courriel est déjà utilisée.')
        ->assertPathIs('/users/create')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertAttribute('#email', 'aria-describedby', 'email-error')
        ->assertScript('document.activeElement.id === "email"');
});

it('shows an error when updating a user with a duplicate email', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->forOrganization($admin->organization)->worker()->create();
    User::factory()->forOrganization($admin->organization)->create(['email' => 'taken@example.com']);
    $page = loginViaUi($admin);

    $page->navigate(sprintf('/users/%s/edit', $worker->id));
    $page->wait(2);

    // Clearing the prefilled email and reusing another user's address must
    // surface the unique rule (the update request ignores the user's own
    // id only)...
    $page->fill('#email', 'taken@example.com')
        ->press('Enregistrer les modifications');

    $page->assertSee('La valeur du champ adresse courriel est déjà utilisée.')
        ->assertPathIs(sprintf('/users/%s/edit', $worker->id));
});

it('is accessible on the user management and billing pages', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->forOrganization($admin->organization)->worker()->create();
    $page = loginViaUi($admin);

    $page->navigate('/users');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/users/create');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate(sprintf('/users/%s/edit', $worker->id));
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $page->navigate('/settings/billing');
    $page->wait(2);
    settleForAccessibilityScan($page);
    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});
