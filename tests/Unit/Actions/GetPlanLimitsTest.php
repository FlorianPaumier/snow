<?php

declare(strict_types=1);

use App\Actions\GetPlanLimits;
use App\Models\Organization;

it('falls back to the free limits without a subscription', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    expect($limits)->toBe([
        'plan' => 'free',
        'maxUsers' => 1,
        'maxClients' => 15,
    ]);
});

it('resolves the unlimited limits of the subscribed plan', function (): void {
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

it('grants pro limits while the signup trial runs', function (): void {
    $organization = Organization::factory()->create(); // with trial...

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    expect($limits['plan'])->toBe('pro')
        ->and($limits['maxUsers'])->toBe(config('plans.pro.limits.maxUsers'))
        ->and($limits['maxClients'])->toBe(config('plans.pro.limits.maxClients'));
});

it('drops back to free limits once the trial has expired', function (): void {
    $organization = Organization::factory()->expired()->create();

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    expect($limits)->toBe([
        'plan' => 'free',
        'maxUsers' => 1,
        'maxClients' => 15,
    ]);
});

it('drops back to free limits when the subscription is canceled', function (): void {
    // A canceled row keeps a future ends_at for the remainder of the paid
    // period, then lapses: only a valid subscription counts...
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_limits_3',
        'stripe_status' => 'canceled',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->subDay(),
    ]);

    $limits = resolve(GetPlanLimits::class)->handle($organization);

    expect($limits)->toBe([
        'plan' => 'free',
        'maxUsers' => 1,
        'maxClients' => 15,
    ]);
});

it('falls back to free limits for an unknown plan type', function (): void {
    // A legacy subscription type must never grant unlimited access...
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

    expect($limits['maxUsers'])->toBe(config('plans.free.limits.maxUsers'))
        ->and($limits['maxClients'])->toBe(config('plans.free.limits.maxClients'));
});
