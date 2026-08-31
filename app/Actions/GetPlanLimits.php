<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;

/**
 * Resolves the plan limits that apply to an organization: the subscribed
 * plan's limits, or — while trialing — the Starter defaults. Unsubscribed
 * organizations get no limits; enforcement is a business decision, not a
 * data-access concern.
 */
final readonly class GetPlanLimits
{
    /**
     * @return array{plan: string, maxUsers: int|null, maxClients: int|null}
     */
    public function handle(Organization $organization): array
    {
        $subscription = $organization->subscription();

        $plan = $subscription !== null
            ? $subscription->name
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
