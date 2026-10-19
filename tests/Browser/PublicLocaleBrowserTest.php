<?php

declare(strict_types=1);

it('starts the public site in French and preserves an explicit English choice', function (): void {
    $page = visit('/')->resize(1440, 900)->assertSee('Moins de paperasse.')
        ->assertAttribute('html[lang]', 'lang', 'fr')->assertSee('Fonctionnalités');
    $page->click('button[aria-pressed="false"]')->assertSee('Less paperwork.')
        ->assertAttribute('html[lang]', 'lang', 'en');
    $page->click('header a[href="/features"]')->assertPathIs('/features')
        ->assertSee('The back office your snow business runs on');
    $page->click('button[aria-pressed="false"]')->assertSee('La gestion de votre entreprise de déneigement')
        ->assertAttribute('html[lang]', 'lang', 'fr')->assertNoAccessibilityIssues(3)->assertNoSmoke();
});

it('offers French first in the mobile language switch', function (): void {
    $page = visit('/features')->resize(390, 844)->assertSee('La gestion de votre entreprise de déneigement');
    $page->click('button[aria-label="Ouvrir le menu de navigation"]')->assertSee('Français')->assertSee('EN')
        ->assertNoAccessibilityIssues(3)->assertNoSmoke();
});
