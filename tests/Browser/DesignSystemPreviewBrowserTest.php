<?php

declare(strict_types=1);

it('updates the demo contract and previews delivery without sending anything', function (): void {
    $page = visit('/design-system')->resize(1440, 900);
    $page->assertSee('A clearer season.')->assertSeeIn('[data-test="season-total"]', '600')
        ->keys('#driveway-price', 'ArrowRight')
        ->assertSeeIn('[data-test="season-total"]', '625')
        ->assertSeeIn('[data-test="remaining-balance"]', '$425');
    $page->click('Preview contract email')->assertSee('No email will be sent.')
        ->click('Run preview')->assertButtonDisabled('button[aria-busy="true"]')->wait(1)
        ->assertSeeIn('[data-sonner-toast]', 'No email was sent.')->assertNoJavascriptErrors();
});

it('keeps the preview accessible on phones in both themes', function (): void {
    $page = visit('/design-system')->resize(390, 844)->wait(1);
    settleForAccessibilityScan($page);
    $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(3)->assertNoSmoke()->screenshot(false, 'design-system-mobile');
    $page->click('button[aria-label="Preview dark theme"]')->wait(1)
        ->assertAttribute('.design-preview', 'data-theme', 'dark')
        ->assertNoAccessibilityIssues(3)->assertNoSmoke()->screenshot(false, 'design-system-mobile-dark');
    $page->click('Preview contract email')->wait(1)->assertNoAccessibilityIssues(3);
});
