<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;

/**
 * Resolves the plan limits that apply to an organization. Every
 * organization always has a plan — Pro while subscribed or trialing,
 * Free otherwise — so the limits come straight from the subscription
 * summary and share its cache entry semantics.
 */
final readonly class GetPlanLimits
{
    public function __construct(
        private TenantCache $cache,
        private GetSubscriptionSummary $getSubscriptionSummary,
    ) {}

    /**
     * @return array{plan: string, maxUsers: int|null, maxClients: int|null}
     */
    public function handle(Organization $organization): array
    {
        /** @var array{plan: string, maxUsers: int|null, maxClients: int|null} */
        return $this->cache->remember(
            'plan-limits',
            // Keyed by the target organization, not just the acting one:
            // in an unauthenticated context (queue job, console command)
            // TenantCache falls back to a single "anon" namespace, and a
            // bare entry name would collide every organization into one
            // shared verdict...
            [$organization->id],
            fn (): array => $this->compute($organization),
            TenantCache::TTL_SHORT,
        );
    }

    /**
     * @return array{plan: string, maxUsers: int|null, maxClients: int|null}
     */
    private function compute(Organization $organization): array
    {
        $plan = $this->getSubscriptionSummary->handle($organization)['plan'] ?? 'free';

        /** @var array{limits: array{maxUsers: int|null, maxClients: int|null}}|null $config */
        $config = config(sprintf('plans.%s', $plan));

        // An unknown plan key (e.g. a legacy subscription type) must never
        // grant unlimited access...
        if ($config === null) {
            $config = config('plans.free');
        }

        /** @var array{limits: array{maxUsers: int|null, maxClients: int|null}} $config */
        return [
            'plan' => $plan,
            'maxUsers' => $config['limits']['maxUsers'],
            'maxClients' => $config['limits']['maxClients'],
        ];
    }
}
