<?php

declare(strict_types=1);

use App\Actions\GetPlanLimits;
use App\Models\Organization;

it('falls back to the starter limits without a subscription', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    expect($limits)->toBe([
        'plan' => 'starter',
        'maxUsers' => 2,
        'maxClients' => 25,
    ]);
});

it('resolves the limits of the subscribed plan', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    // The checkout stores the subscription with the plan key as its type...
    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_limits_1',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    /** @var array{maxUsers: int|null, maxClients: int|null} $planLimits */
    $planLimits = config('plans.pro.limits');

    expect($limits)->toBe([
        'plan' => 'pro',
        'maxUsers' => $planLimits['maxUsers'],
        'maxClients' => $planLimits['maxClients'],
    ]);
});

it('falls back to starter limits for an unknown plan type', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'not-a-plan',
        'stripe_id' => 'sub_limits_2',
        'stripe_status' => 'active',
        'stripe_price' => 'price_unknown',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    expect($limits['plan'])->toBe('not-a-plan')
        ->and($limits['maxUsers'])->toBe(config('plans.starter.limits.maxUsers'))
        ->and($limits['maxClients'])->toBe(config('plans.starter.limits.maxClients'));
});
