<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Visual regression tests for the auth pages. These protect the Frostline
 * styling (split layout, brand panel, cards, shadows) from accidental changes.
 *
 * Update the baseline screenshots after intentional style changes:
 *
 *     php artisan test --browse --update-snapshots
 */
it('matches the login page visual on desktop', function (): void {
    $page = visit('/login')
        ->resize(1440, 900);

    $page->assertScreenshotMatches();
});

it('matches the login page visual on mobile', function (): void {
    $page = visit('/login')
        ->resize(390, 844);

    $page->assertScreenshotMatches();
});

it('matches the forgot password page visual', function (): void {
    $page = visit('/forgot-password')
        ->resize(1440, 900);

    $page->assertScreenshotMatches();
});

it('matches the login challenge page visual', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'name' => 'Visual Tester',
        'email' => 'visual@example.com',
        'password' => 'password1234',
    ]);

    $page = visit('/login')
        ->resize(1440, 900)
        ->fill('#email', $user->email)
        ->fill('#password', 'password1234')
        ->press('Log in');

    $page->wait(2);

    $page->assertPathIs('/login-challenge');

    $page->assertScreenshotMatches();
});
