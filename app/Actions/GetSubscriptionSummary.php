<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;
use Laravel\Cashier\Subscription;

/**
 * A compact subscription summary shared with every Inertia request. Lives on
 * the organization (the company pays, not the user).
 */
final readonly class GetSubscriptionSummary
{
    public function __construct(private TenantCache $cache) {}

    /**
     * @return array{plan: string|null, status: string, onTrial: bool, trialEndsAt: string|null}|null
     */
    public function handle(?Organization $organization): ?array
    {
        if (! $organization instanceof Organization) {
            return null;
        }

        /** @var array{plan: string|null, status: string, onTrial: bool, trialEndsAt: string|null} */
        return $this->cache->remember(
            'subscription-summary',
            [],
            fn (): array => $this->compute($organization),
            TenantCache::TTL_LONG,
        );
    }

    /**
     * @return array{plan: string|null, status: string, onTrial: bool, trialEndsAt: string|null}
     */
    private function compute(Organization $organization): array
    {
        // The checkout creates the subscription with the plan key as its
        // type, so any single active subscription is the plan...
        $subscription = $organization->subscriptions->first();

        if (! $subscription instanceof Subscription) {
            $onTrial = $organization->onTrial();

            return [
                'plan' => null,
                'status' => $onTrial ? 'trialing' : 'none',
                'onTrial' => $onTrial,
                'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
            ];
        }

        return [
            // Cashier keeps the plan key in the subscription's type column...
            'plan' => $subscription->type,
            'status' => $subscription->stripe_status,
            'onTrial' => $subscription->onTrial(),
            'trialEndsAt' => $subscription->trial_ends_at?->toIso8601String(),
        ];
    }
}
