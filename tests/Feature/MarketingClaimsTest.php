<?php

declare(strict_types=1);

/*
 * Marketing claims must match what ships. The application is a
 * client/contract/payment back office — it has no dispatch, routing,
 * driver app, photo proof, client portal, or public API. These tests
 * keep fabricated product claims off the public pages: if one fails,
 * either ship the feature or change the copy.
 *
 * phpunit.xml disables SSR for the suite (fast client-side-only pages),
 * but the claims scan needs the server-rendered copy — so this file
 * re-enables SSR for its own process and verifies the page body really
 * contains rendered copy. Otherwise the scan would silently pass
 * against an empty client-only shell. SSR requires a built bundle:
 * `npm run build:ssr` (and the SSR server, which Inertia starts
 * lazily on first request when a bundle exists).
 */

beforeEach(function (): void {
    config(['inertia.ssr.enabled' => true]);
});

it('renders marketing copy server-side so claims can be scanned', function (): void {
    $body = $this->get('/features')->getContent();

    // Sanity anchor: the features hero is present in the rendered HTML.
    // If this fails, SSR is off/broken and every claims scan below is
    // testing an empty shell — fix SSR before trusting these tests.
    expect(mb_strtolower((string) $body))->toContain('back office your snow business runs on');
});

it('never advertises features that do not exist', function (string $path): void {
    $body = mb_strtolower((string) $this->get($path)->getContent());

    foreach (blockedSubstringClaims() as $claim) {
        expect($body)->not->toContain($claim);
    }

    foreach (blockedWordClaims() as $word) {
        expect($body)->not->toMatch(sprintf('/\b%s\b/', $word));
    }
})->with([
    '/' => ['/'],
    'features' => ['/features'],
    'pricing' => ['/pricing'],
    'about' => ['/about'],
    'contact' => ['/contact'],
    'terms' => ['/terms'],
    'privacy' => ['/privacy'],
    'status' => ['/status'],
]);

/**
 * Claims checked as plain substrings (lowercase). None of these strings
 * occur inside innocent words, so a substring match is a real hit.
 *
 * @return list<string>
 */
function blockedSubstringClaims(): array
{
    return [
        // Field-operations features that do not exist
        'auto-dispatch',
        'weather-triggered dispatch',
        'trigger-based auto-dispatch',
        'trigger depth',
        'route optimization',
        're-route',
        're-routing',
        'driver app',
        'driver-first mobile app',
        'offline-first',
        'photo proof',
        'proof of service',
        'geo-stamped',
        'client portal',
        'pavement',

        // Platform features that do not exist
        'api access',
        'webhooks',
        'audit logs',
        'uptime sla',
        'accounting sync',
        'in-app chat',

        // Fabricated traction and operations stats
        '4.2m',
        '1,200+ crews',
        '11 min',
        'median dispatch',
        'crews dispatched',
        'properties cleared',
        'trusted on',
        'founded 2021',
        'storm-season support',
        'seasonal pause',
        '$19/mo',
    ];
}

/**
 * Claims checked with word boundaries (lowercase) — substrings that
 * also live inside innocent words (e.g. "sso" in "processor").
 *
 * @return list<string>
 */
function blockedWordClaims(): array
{
    return [
        'sso',
        'webhook',
    ];
}
