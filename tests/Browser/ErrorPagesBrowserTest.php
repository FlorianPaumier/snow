<?php

declare(strict_types=1);

it('keeps error pages minimal and accessible on desktop and mobile', function (): void {
    $page = visit('/this-page-does-not-exist')->resize(1440, 900);
    settleForAccessibilityScan($page);
    $page->assertSee('404')->assertMissing('header')->assertMissing('footer')
        ->assertNoAccessibilityIssues(3)->assertNoSmoke()->screenshot(false, 'error-desktop');
    $page->resize(390, 844);
    $page->wait(1)->assertNoAccessibilityIssues(3);
    $page
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertAttribute('a[href="/"]', 'href', '/')->screenshot(false, 'error-mobile')
        ->click('a[href="/"]')->assertPathIs('/')->assertSee('Moins de paperasse.');
});
