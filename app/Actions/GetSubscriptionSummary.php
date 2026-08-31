<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;

/**
 * A compact subscription summary shared with every Inertia request. Lives on
 * the organization (the company pays, not the user).
 */
final class GetSubscriptionSummary
{
    /**
     * @return array{plan: string|null, status: string, onTrial: bool, trialEndsAt: string|null}|null
     */
    public function handle(?Organization $organization): ?array
    {
        if ($organization === null) {
            return null;
        }

        $subscription = $organization->subscription();

        if ($subscription === null) {
            return [
                'plan' => null,
                'status' => 'trialing',
                'onTrial' => true,
                'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
            ];
        }

        return [
            'plan' => $subscription->name,
            'status' => $subscription->stripe_status,
            'onTrial' => $subscription->onTrial(),
            'trialEndsAt' => $subscription->trial_ends_at?->toIso8601String(),
        ];
    }
}
