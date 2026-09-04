<?php

declare(strict_types=1);

use App\Actions\GetPlanFeatures;
use App\Models\Organization;

it('locks payment reminders without a subscription', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $features = resolve(GetPlanFeatures::class)->handle($organization);

    expect($features)->toBe([
        'paymentReminders' => false,
    ]);
});

it('locks payment reminders once the signup trial has expired', function (): void {
    $organization = Organization::factory()->expired()->create();

    $features = resolve(GetPlanFeatures::class)->handle($organization);

    expect($features)->toBe([
        'paymentReminders' => false,
    ]);
});

it('unlocks payment reminders while the signup trial runs', function (): void {
    $organization = Organization::factory()->create(); // with trial...

    $features = resolve(GetPlanFeatures::class)->handle($organization);

    expect($features)->toBe([
        'paymentReminders' => true,
    ]);
});

it('unlocks payment reminders for a subscribed organization', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    // The checkout stores the subscription with the plan key as its type...
    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_features_1',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $features = resolve(GetPlanFeatures::class)->handle($organization);

    expect($features)->toBe([
        'paymentReminders' => true,
    ]);
});

it('keeps payment reminders locked for an unknown plan type', function (): void {
    // A legacy subscription type must never unlock paid features...
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'not-a-plan',
        'stripe_id' => 'sub_features_2',
        'stripe_status' => 'active',
        'stripe_price' => 'price_unknown',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $features = resolve(GetPlanFeatures::class)->handle($organization);

    expect($features)->toBe([
        'paymentReminders' => false,
    ]);
});
