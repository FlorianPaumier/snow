<?php

declare(strict_types=1);

use App\Actions\GetSubscriptionSummary;
use App\Models\Organization;

it('returns null without an organization', function (): void {
    $summary = resolve(GetSubscriptionSummary::class)->handle(null);

    expect($summary)->toBeNull();
});

it('reports an active trial as trialing', function (): void {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->addDays(14),
    ]);

    $summary = resolve(GetSubscriptionSummary::class)->handle($organization);

    expect($summary)->toBe([
        'plan' => null,
        'status' => 'trialing',
        'onTrial' => true,
        'trialEndsAt' => $organization->trial_ends_at->toIso8601String(),
    ]);
});

it('reports an expired trial as no longer on trial', function (): void {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);

    $summary = resolve(GetSubscriptionSummary::class)->handle($organization);

    expect($summary)->toBe([
        'plan' => null,
        'status' => 'none',
        'onTrial' => false,
        'trialEndsAt' => $organization->trial_ends_at->toIso8601String(),
    ]);
});

it('reports a trial-less organization as not on trial', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $summary = resolve(GetSubscriptionSummary::class)->handle($organization);

    expect($summary)->toBe([
        'plan' => null,
        'status' => 'none',
        'onTrial' => false,
        'trialEndsAt' => null,
    ]);
});

it('reports the plan and status of a subscribed organization', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    // The checkout stores the subscription with the plan key as its type...
    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_test_1',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $summary = resolve(GetSubscriptionSummary::class)->handle($organization);

    expect($summary)->toBe([
        'plan' => 'pro',
        'status' => 'active',
        'onTrial' => false,
        'trialEndsAt' => null,
    ]);
});

it('reports a subscription on trial as trialing with its plan', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $trialEndsAt = now()->addDays(7);

    $organization->subscriptions()->create([
        'type' => 'fleet',
        'stripe_id' => 'sub_test_2',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_fleet',
        'quantity' => 1,
        'trial_ends_at' => $trialEndsAt,
        'ends_at' => null,
    ]);

    $summary = resolve(GetSubscriptionSummary::class)->handle($organization);

    expect($summary)->toBe([
        'plan' => 'fleet',
        'status' => 'trialing',
        'onTrial' => true,
        'trialEndsAt' => $trialEndsAt->toIso8601String(),
    ]);
});
