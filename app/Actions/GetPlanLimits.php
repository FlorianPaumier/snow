<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;
use Laravel\Cashier\Subscription;

/**
 * Resolves the plan limits that apply to an organization: the subscribed
 * plan's limits, or — while trialing — the Starter defaults. Unsubscribed
 * organizations get no limits; enforcement is a business decision, not a
 * data-access concern.
 */
final readonly class GetPlanLimits
{
    public function __construct(private TenantCache $cache) {}

    /**
     * @return array{plan: string, maxUsers: int|null, maxClients: int|null}
     */
    public function handle(Organization $organization): array
    {
        /** @var array{plan: string, maxUsers: int|null, maxClients: int|null} */
        return $this->cache->remember(
            'plan-limits',
            [],
            fn (): array => $this->compute($organization),
            TenantCache::TTL_LONG,
        );
    }

    /**
     * @return array{plan: string, maxUsers: int|null, maxClients: int|null}
     */
    private function compute(Organization $organization): array
    {
        // The checkout creates the subscription with the plan key as its
        // type, so any single active subscription is the plan...
        $subscription = $organization->subscriptions->first();

        // Cashier keeps the plan key in the subscription's type column...
        $plan = $subscription instanceof Subscription
            ? $subscription->type
            : 'starter';

        /** @var array{limits: array{maxUsers: int|null, maxClients: int|null}} $config */
        $config = config(sprintf('plans.%s', $plan), config('plans.starter'));

        return [
            'plan' => $plan,
            'maxUsers' => $config['limits']['maxUsers'],
            'maxClients' => $config['limits']['maxClients'],
        ];
    }
}
