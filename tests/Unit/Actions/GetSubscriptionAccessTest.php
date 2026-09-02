<?php

declare(strict_types=1);

use App\Actions\GetSubscriptionAccess;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolve the action with the organization as the acting tenant. The
 * cache keys off Auth::user(), so tests act as a member of the org.
 */
function accessFor(Organization $organization): array
{
    $user = User::factory()->forOrganization($organization)->create();

    Auth::login($user);

    /** @var array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null} */
    return resolve(GetSubscriptionAccess::class)->handle($organization);
}

it('returns a full-access verdict without an organization', function (): void {
    $verdict = resolve(GetSubscriptionAccess::class)->handle(null);

    expect($verdict)->toBe([
        'readOnly' => false,
        'unlockAvailable' => false,
        'unlockedUntil' => null,
        'trialEndsAt' => null,
    ]);
});

it('grants full access while the generic trial runs', function (): void {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->addDays(14),
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse()
        ->and($verdict['trialEndsAt'])->toBe($organization->trial_ends_at->toIso8601String())
        ->and($verdict['unlockedUntil'])->toBeNull()
        ->and($verdict['unlockAvailable'])->toBeFalse();
});

it('marks an expired trial without a subscription as read-only with the unlock available', function (): void {
    $organization = Organization::factory()->expired()->create();

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeTrue()
        ->and($verdict['trialEndsAt'])->toBe($organization->trial_ends_at->toIso8601String())
        ->and($verdict['unlockedUntil'])->toBeNull();
});

it('marks a trial-less unsubscribed organization as read-only', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeTrue()
        ->and($verdict['trialEndsAt'])->toBeNull();
});

it('grants full access for a valid subscription', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    // The checkout stores the subscription with the plan key as its type...
    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_access_1',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse();
});

it('treats a canceled subscription still in its grace period as full access', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_access_2',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(10), // canceled, running to period end...
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse();
});

it('treats a past-due subscription as read-only under Cashier defaults', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_access_3',
        'stripe_status' => 'past_due',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeTrue();
});

it('grants full access while a 24h unlock is running', function (): void {
    $organization = Organization::factory()->expired()->unlocked()->create();

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse()
        ->and($verdict['unlockedUntil'])->toBe($organization->access_unlock_until->toIso8601String())
        ->and($verdict['unlockAvailable'])->toBeFalse();
});

it('never offers the unlock again once it has been used', function (): void {
    $organization = Organization::factory()->expired()->create([
        'access_unlock_until' => now()->subHour(), // used and expired...
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeFalse();
});

it('caps the cache TTL at the trial end when it is the soonest transition', function (): void {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->addMinutes(5),
    ]);

    $user = User::factory()->forOrganization($organization)->create();
    Auth::login($user);

    resolve(GetSubscriptionAccess::class)->handle($organization);

    // ArrayStore exposes raw storage: find the entry by name fragment
    // (the key embeds a UUID version token)...
    /** @var Illuminate\Cache\ArrayStore $arrayStore */
    $arrayStore = cache()->store()->getStore();

    $reflection = new ReflectionClass($arrayStore);
    $property = $reflection->getProperty('storage');
    $entries = $property->getValue($arrayStore);

    $accessEntries = array_filter(
        $entries,
        fn (array $entry, string $key): bool => str_contains($key, ':subscription-access:'),
        ARRAY_FILTER_USE_BOTH,
    );

    expect($accessEntries)->not->toBeEmpty();

    $entry = reset($accessEntries);
    $remaining = $entry['expiresAt'] - now()->getPreciseTimestamp(3) / 1000;

    expect($remaining)->toBeGreaterThan(0)->toBeLessThanOrEqual(300);
});

it('caps the cache TTL at the unlock expiry when it is the soonest transition', function (): void {
    $organization = Organization::factory()->expired()->create([
        'access_unlock_until' => now()->addMinutes(10),
    ]);

    $user = User::factory()->forOrganization($organization)->create();
    Auth::login($user);

    resolve(GetSubscriptionAccess::class)->handle($organization);

    /** @var Illuminate\Cache\ArrayStore $arrayStore */
    $arrayStore = cache()->store()->getStore();

    $reflection = new ReflectionClass($arrayStore);
    $property = $reflection->getProperty('storage');
    $entries = $property->getValue($arrayStore);

    $accessEntries = array_filter(
        $entries,
        fn (array $entry, string $key): bool => str_contains($key, ':subscription-access:'),
        ARRAY_FILTER_USE_BOTH,
    );

    expect($accessEntries)->not->toBeEmpty();

    $entry = reset($accessEntries);
    $remaining = $entry['expiresAt'] - now()->getPreciseTimestamp(3) / 1000;

    expect($remaining)->toBeGreaterThan(0)->toBeLessThanOrEqual(600);
});
