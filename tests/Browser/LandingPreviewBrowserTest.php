<?php

declare(strict_types=1);

it('presents the new landing direction with a working contract demo', function (): void {
    $page = visit('/')->resize(1440, 900)->assertSee('Moins de paperasse.');
    $page->keys('#driveway-price', 'ArrowRight')->assertSeeIn('[data-test="season-total"]', '625')
        ->assertAttribute('[data-test="landing-trial"]', 'href', '/contact');
    settleForAccessibilityScan($page);
    $page->wait(1)->assertNoAccessibilityIssues(3)->assertNoSmoke()->screenshot(false, 'landing-desktop');
});

it('keeps navigation and questions usable on a phone', function (): void {
    $page = visit('/')->resize(390, 844)->wait(1);
    settleForAccessibilityScan($page);
    $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(3)->screenshot(false, 'landing-mobile')
        ->click('button[aria-label="Ouvrir le menu de navigation"]')->assertVisible('nav[aria-label="Navigation mobile"]')
        ->click('nav[aria-label="Navigation mobile"] a[href="/pricing"]')->assertPathIs('/pricing')
        ->assertNoSmoke();
});
