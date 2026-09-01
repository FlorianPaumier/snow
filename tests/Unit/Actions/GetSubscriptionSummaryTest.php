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
