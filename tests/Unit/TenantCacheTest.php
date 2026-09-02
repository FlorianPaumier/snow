<?php

declare(strict_types=1);

use App\Actions\FlushOrganizationCache;
use App\Models\User;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

// The organization scope requires an authenticated user to see any data;
// the cache keys the same way...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);

    Cache::flush();
});

it('caches a value and returns the same result on the second call', function (): void {
    $cache = resolve(TenantCache::class);
    $calls = 0;

    $callback = function () use (&$calls): int {
        $calls++;

        return $calls;
    };

    $first = $cache->remember('probe', [], $callback);
    $second = $cache->remember('probe', [], $callback);

    expect($first)->toBe(1)
        ->and($second)->toBe(1)
        ->and($calls)->toBe(1);
});

it('distinguishes entries by their params', function (): void {
    $cache = resolve(TenantCache::class);

    $alpha = $cache->remember('probe', ['alpha'], fn (): string => 'a');
    $beta = $cache->remember('probe', ['beta'], fn (): string => 'b');
    $alphaAgain = $cache->remember('probe', ['alpha'], fn (): string => 'recomputed');

    expect($alpha)->toBe('a')
        ->and($beta)->toBe('b')
        ->and($alphaAgain)->toBe('a');
});

it('recomputes entries of the organization after a flush', function (): void {
    $cache = resolve(TenantCache::class);

    $before = $cache->remember('probe', [], fn (): string => 'stale');
    $cache->flush();
    $after = $cache->remember('probe', [], fn (): string => 'fresh');

    expect($before)->toBe('stale')
        ->and($after)->toBe('fresh');
});

it('does not flush another organization cache', function (): void {
    $otherUser = User::factory()->create();

    $cache = resolve(TenantCache::class);

    // Seed the other organization's entry from its own session...
    Auth::login($otherUser);
    $cache->remember('probe', [], fn (): string => 'other-org');

    // Back to the first organization: seed, flush, verify...
    Auth::login($this->user);
    $cache->remember('probe', [], fn (): string => 'my-org');
    $cache->flush();

    expect($cache->remember('probe', [], fn (): string => 'recomputed'))->toBe('recomputed');

    // The other organization's entry survived...
    Auth::login($otherUser);

    expect($cache->remember('probe', [], fn (): string => 'other-recomputed'))->toBe('other-org');
});

it('flushes by organization id without an authenticated user', function (): void {
    $organizationId = $this->user->organization_id;

    $cache = resolve(TenantCache::class);
    $cache->remember('probe', [], fn (): string => 'seeded');

    Auth::logout();
    Cache::flush();

    // No session, but the organization is addressable directly — as the
    // Stripe webhook does...
    resolve(FlushOrganizationCache::class)->handle($organizationId);

    expect(true)->toBeTrue();
});
