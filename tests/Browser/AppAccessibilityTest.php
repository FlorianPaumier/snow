<?php

declare(strict_types=1);

use App\Models\User;

it('supports skipping navigation and exposes the current user sort', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->worker()->forOrganization($admin->organization)->create(['name' => 'Camille Tremblay']);
    $page = loginViaUi($admin)->navigate('/users');
    $page->assertSee('Administrateur')->assertSee('Opérateur')
        ->keys('a[href="#main-content"]', 'Enter')->assertScript('document.activeElement.id === "main-content"')
        ->click('Nom')->assertAttribute('th[aria-sort]', 'aria-sort', 'ascending')
        ->click('Nom')->assertAttribute('th[aria-sort]', 'aria-sort', 'descending')
        ->assertAttribute('[data-slot="table-container"]', 'tabindex', '0')
        ->assertNoAccessibilityIssues(3)->assertNoSmoke()->screenshot(false, 'users-accessible-desktop');
    $page->resize(390, 844)->wait(1)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(3)->screenshot(false, 'users-accessible-mobile');
});

it('keeps the shared app shell and primary pages accessible', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);
    $failures = [];
    foreach (['/dashboard', '/clients', '/clients/create', '/contracts', '/contracts/create', '/quotes', '/quotes/create', '/contracts/renewals', '/tours', '/tours/create', '/tour-templates', '/tour-templates/create', '/users', '/users/create', '/users/import', '/settings/profile', '/settings/password', '/settings/appearance', '/settings/language', '/settings/organization', '/settings/services', '/settings/billing'] as $path) {
        $page->navigate($path);
        settleForAccessibilityScan($page);
        try {
            $page->assertVisible('a[href="#main-content"]')->assertNoAccessibilityIssues(3)->assertNoSmoke();
        } catch (PHPUnit\Framework\ExpectationFailedException $exception) {
            $failures[] = $path.': '.$exception->getMessage();
        }
    }
    expect($failures)->toBeEmpty();
});

it('lets keyboard users reveal and hide their password', function (): void {
    $page = visit('/login');
    $page->fill('#password', 'private-example')
        ->keys('button[aria-label="Afficher le mot de passe"]', 'Enter')
        ->assertAttribute('#password', 'type', 'text')
        ->assertAttribute('button[aria-label="Masquer le mot de passe"]', 'aria-pressed', 'true')
        ->keys('button[aria-label="Masquer le mot de passe"]', 'Enter')
        ->assertAttribute('#password', 'type', 'password')->assertNoAccessibilityIssues(3)->assertNoSmoke();
});

it('keeps touch controls usable on a phone', function (): void {
    $page = visit('/login')->on()->mobile();
    settleForAccessibilityScan($page);
    $page->assertScript('Array.from(document.querySelectorAll("[data-slot=button], [data-slot=input]")).every(el => el.getBoundingClientRect().height >= 44)')
        ->assertNoAccessibilityIssues(3)->assertNoSmoke();
});

it('keeps the users page and destructive confirmation accessible in dark mode', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->worker()->forOrganization($admin->organization)->create(['name' => 'Camille Tremblay']);
    $page = loginViaUi($admin)->navigate('/settings/appearance');
    $page->click('Sombre')->navigate('/users')->wait(1)->assertNoAccessibilityIssues(3)
        ->click('button[aria-label="Supprimer Camille Tremblay"]')->wait(1)
        ->assertVisible('[role="dialog"]')->assertNoAccessibilityIssues(3)->assertNoSmoke()
        ->keys('[role="dialog"]', 'Escape')->assertMissing('[role="dialog"]');
});
