<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;

/**
 * Resolves the feature flags that apply to an organization: the paid-tier
 * gates from the plan config (payment reminders & notifications). Like
 * the plan limits, every organization always has a plan — Pro while
 * subscribed or trialing, Free otherwise.
 */
final readonly class GetPlanFeatures
{
    public function __construct(
        private TenantCache $cache,
        private GetSubscriptionSummary $getSubscriptionSummary,
    ) {}

    /**
     * @return array{paymentReminders: bool}
     */
    public function handle(Organization $organization): array
    {
        /** @var array{paymentReminders: bool} */
        return $this->cache->remember(
            'plan-features',
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
     * @return array{paymentReminders: bool}
     */
    private function compute(Organization $organization): array
    {
        $plan = $this->getSubscriptionSummary->handle($organization)['plan'] ?? 'free';

        /** @var array{features: array{paymentReminders: bool}}|null $config */
        $config = config(sprintf('plans.%s', $plan));

        // An unknown plan key (e.g. a legacy subscription type) must never
        // unlock paid features...
        if ($config === null) {
            $config = config('plans.free');
        }

        /** @var array{features: array{paymentReminders: bool}} $config */
        return $config['features'];
    }
}
