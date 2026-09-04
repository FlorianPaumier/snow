<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Subscription;

/**
 * A compact subscription summary shared with every Inertia request. Lives on
 * the organization (the company pays, not the user). Every organization
 * always has a plan: Pro while a valid subscription (or the signup trial —
 * the trial shows off the full product) is running, Free otherwise.
 */
final readonly class GetSubscriptionSummary
{
    private const int TTL_CEILING = 3600;

    public function __construct(private TenantCache $cache) {}

    /**
     * @return array{plan: string, status: string, onTrial: bool, trialEndsAt: string|null}|null
     */
    public function handle(?Organization $organization): ?array
    {
        if (! $organization instanceof Organization) {
            return null;
        }

        /** @var array{plan: string, status: string, onTrial: bool, trialEndsAt: string|null} */
        return $this->cache->remember(
            'subscription-summary',
            [$organization->id],
            fn (): array => $this->compute($organization),
            $this->ttl($organization),
        );
    }

    /**
     * @return array{plan: string, status: string, onTrial: bool, trialEndsAt: string|null}
     */
    private function compute(Organization $organization): array
    {
        // The checkout creates the subscription with the plan key as its
        // type, so the first valid row is the plan; a canceled or past-due
        // row drops the organization back to the Free plan...
        /** @var Collection<int, Subscription> $subscriptions */
        $subscriptions = $organization->subscriptions;

        $subscription = $subscriptions->first(fn (Subscription $subscription): bool => $subscription->valid());

        if ($subscription instanceof Subscription) {
            return [
                // Cashier keeps the plan key in the subscription's type column...
                'plan' => $subscription->type,
                'status' => $subscription->stripe_status,
                'onTrial' => $subscription->onTrial(),
                'trialEndsAt' => $subscription->trial_ends_at?->toIso8601String(),
            ];
        }

        // While the signup trial runs, the organization enjoys the full
        // product; when it lapses the Free caps simply apply...
        $onTrial = $organization->onGenericTrial();

        return [
            'plan' => $onTrial ? 'pro' : 'free',
            'status' => $onTrial ? 'trialing' : 'free',
            'onTrial' => $onTrial,
            'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
        ];
    }

    /**
     * Cap the entry's life at the trial's end so a cached "Pro by trial"
     * summary can never outlive the moment it flips to Free...
     */
    private function ttl(Organization $organization): int
    {
        $horizon = now()->addSeconds(self::TTL_CEILING);

        if ($organization->trial_ends_at?->isFuture() === true) {
            $horizon = $horizon->min($organization->trial_ends_at);
        }

        // Carbon 3 diffs are signed: the horizon is in the future of now,
        // so now must be the earlier operand or the diff is negative...
        return max(1, (int) now()->diffInSeconds($horizon));
    }
}
