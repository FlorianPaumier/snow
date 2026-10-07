<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

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
 * `bun run build:ssr`. The test starts its own SSR server on an isolated port.
 */

$ssrProcess = null;
$ssrUrl = null;

beforeAll(function () use (&$ssrProcess, &$ssrUrl): void {
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    throw_if($socket === false, RuntimeException::class, 'Unable to allocate the test SSR port.');

    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    throw_if($address === false, RuntimeException::class, 'Unable to resolve the test SSR port.');

    $port = mb_substr($address, mb_strrpos($address, ':') + 1);
    $root = dirname(__DIR__, 2);
    $ssrUrl = 'http://127.0.0.1:'.$port;
    $ssrProcess = new Process(['node', $root.'/bootstrap/ssr/ssr.js'], $root, ['INERTIA_SSR_PORT' => $port]);
    $ssrProcess->setTimeout(10);
    $ssrProcess->start();
    if (! $ssrProcess->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Inertia SSR server started.'))) {
        throw new RuntimeException('The test SSR server failed to start: '.$ssrProcess->getErrorOutput());
    }

    $ssrProcess->setTimeout(null);
});

afterAll(function () use (&$ssrProcess): void {
    $ssrProcess?->stop();
});

beforeEach(function () use (&$ssrUrl): void {
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.url' => $ssrUrl, 'inertia.ssr.throw_on_error' => true]);
    Http::allowStrayRequests([config()->string('inertia.ssr.url').'/*']);
});

it('renders marketing copy server-side so claims can be scanned', function (): void {
    $body = (string) $this->get('/')->getContent();

    // Sanity anchor: the home hero is present in the rendered HTML.
    // If this fails, SSR is off/broken and every claims scan below is
    // testing an empty shell — fix SSR before trusting these tests.
    expect(mb_strtolower($body))->toContain('organized to the last balance');
    expect($body)
        ->toContain('Frostline OPS — Snow Removal Contract &amp; Payment Software</title>')
        ->toContain('<link rel="canonical" href="http://localhost/"')
        ->toContain('property="og:image" content="http://localhost/images/plow-night.jpg"')
        ->toContain('name="twitter:image" content="http://localhost/images/plow-night.jpg"')
        ->toContain('"url":"http://localhost/"');
});

it('does not index private pages', function (): void {
    config(['inertia.ssr.enabled' => false]);

    $this->get('/login')->assertSee('name="robots" content="noindex, nofollow"', false);
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

        // Invoicing ships as print + PDF download — there is no email
        // delivery of invoices or receipts
        'email invoices',
        'email receipts',
        'email an invoice',
        'email a receipt',
        'receipt emailed',
        'receipts emailed',
        'invoice emailed',
        'invoices emailed',
        'invoiced by email',
        'receipts by email',

        // An expired trial keeps the organization writable under the Free
        // plan caps — it is never a read-only view
        'read-only view',
        'read only view',

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
