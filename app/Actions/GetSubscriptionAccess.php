<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;
use Laravel\Cashier\Subscription;

/**
 * The organization's access verdict: full access (valid subscription, running
 * trial, or active unlock) or read-only. One cached entry serves both the
 * enforcement middleware and the shared banner prop, so they can never
 * disagree.
 */
final readonly class GetSubscriptionAccess
{
    private const int TTL_CEILING = 900;

    public function __construct(private TenantCache $cache) {}

    /**
     * @return array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null}
     */
    public function handle(?Organization $organization): array
    {
        if (! $organization instanceof Organization) {
            return [
                'readOnly' => false,
                'unlockAvailable' => false,
                'unlockedUntil' => null,
                'trialEndsAt' => null,
            ];
        }

        /** @var array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null} */
        return $this->cache->remember(
            'subscription-access',
            // Keyed by the target organization, not just the acting one:
            // in an unauthenticated context (queue job, console command)
            // TenantCache falls back to a single "anon" namespace, and a
            // bare entry name would collide every organization into one
            // shared verdict...
            [$organization->id],
            fn (): array => $this->compute($organization),
            $this->ttl($organization),
        );
    }

    /**
     * @return array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null}
     */
    private function compute(Organization $organization): array
    {
        // The checkout creates the subscription with the plan key as its
        // type, so the first row is the plan (the same convention as
        // GetSubscriptionSummary / GetPlanLimits)...
        /** @var \Illuminate\Database\Eloquent\Collection<int, Subscription> $subscriptions */
        $subscriptions = $organization->subscriptions;

        $validSubscription = $subscriptions
            ->first(fn (Subscription $subscription): bool => $subscription->valid());

        $unlockedUntil = $organization->access_unlock_until;

        $readOnly = $validSubscription === null
            && ! $organization->onGenericTrial()
            && ($unlockedUntil === null || $unlockedUntil->isPast());

        return [
            'readOnly' => $readOnly,
            'unlockAvailable' => $readOnly && $unlockedUntil === null,
            'unlockedUntil' => $unlockedUntil?->isFuture() === true ? $unlockedUntil->toIso8601String() : null,
            'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
        ];
    }

    /**
     * Cap the entry's life at the next known time transition so a cached
     * "full access" verdict can never outlive the moment it could flip.
     */
    private function ttl(Organization $organization): int
    {
        $horizon = now()->addSeconds(self::TTL_CEILING);

        if ($organization->trial_ends_at?->isFuture() === true) {
            $horizon = $horizon->min($organization->trial_ends_at);
        }

        if ($organization->access_unlock_until?->isFuture() === true) {
            $horizon = $horizon->min($organization->access_unlock_until);
        }

        // Carbon 3 diffs are signed: the horizon is in the future of now,
        // so now must be the earlier operand or the diff is negative...
        $ttl = now()->diffInSeconds($horizon);

        return max(1, (int) $ttl);
    }
}
