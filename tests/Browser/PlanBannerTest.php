<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

/**
 * The plan banner appears on every app page while the organization is on
 * its signup trial or on the Free plan. Its "View Pro" button targets the
 * billing page, which is admin-only (can:manage-billing) — a worker
 * following it from the banner would land on a 403. The button is
 * therefore rendered for admins only, while the banner text alone informs
 * everyone else.
 *
 * The browser has no locale cookie, so the French default applies (see
 * SetLocale) — the assertions pin the French banner strings.
 */
it('shows the upgrade button to an admin on a capped plan', function (): void {
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    loginViaUi($admin)
        ->navigate('/clients')
        ->wait(2)
        ->assertSee('Voir Pro');
});

it('hides the upgrade button from a worker on a capped plan', function (): void {
    $organization = Organization::factory()->expired()->create();
    User::factory()->admin()->forOrganization($organization)->create();
    $worker = User::factory()->worker()->forOrganization($organization)->create();

    $page = loginViaUi($worker);

    $page->navigate('/clients');
    $page->wait(2);

    // The Free-plan banner text still informs the worker...
    $page->assertSee('Vous êtes sur le forfait gratuit')
        ->assertDontSee('Voir Pro');
});

it('hides the upgrade button from a worker during the trial', function (): void {
    $organization = Organization::factory()->create(); // trial runs
    User::factory()->admin()->forOrganization($organization)->create();
    $worker = User::factory()->worker()->forOrganization($organization)->create();

    $page = loginViaUi($worker);

    $page->navigate('/clients');
    $page->wait(2);

    $page->assertSee('Votre essai gratuit est en cours')
        ->assertDontSee('Voir Pro');
});
