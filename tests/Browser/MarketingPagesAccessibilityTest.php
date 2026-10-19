<?php

declare(strict_types=1);

/**
 * Accessibility coverage for the public marketing pages at the strictest
 * axe level (minor issues included). The palette has been tuned for WCAG AA
 * text contrast; settleForAccessibilityScan guards the document-level rules
 * against sampling before the Inertia shell renders.
 */
it('is accessible on the marketing pages', function (string $path): void {
    $page = visit($path);
    settleForAccessibilityScan($page);

    $page->wait(1)->assertNoAccessibilityIssues(3)
        ->click('button[aria-label="Changer le thème de couleur"]')->wait(1)->assertNoAccessibilityIssues(3)
        ->resize(390, 844)->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
})->with([
    'home' => '/',
    'features' => '/features',
    'pricing' => '/pricing',
    'about' => '/about',
    'contact' => '/contact',
    'privacy' => '/privacy',
    'terms' => '/terms',
    'status' => '/status',
]);
