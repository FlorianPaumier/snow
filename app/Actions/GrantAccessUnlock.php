<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\OrganizationReadOnly;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Grants a read-only organization its one-time 24h full-access unlock.
 * Available regardless of the cause (expired trial or failed payment),
 * but only once per organization lifetime.
 */
final readonly class GrantAccessUnlock
{
    public function __construct(
        private GetSubscriptionAccess $getSubscriptionAccess,
        private FlushOrganizationCache $flushCache,
    ) {}

    public function handle(User $admin): void
    {
        /** @var Organization $organization */
        $organization = $admin->organization;

        DB::transaction(function () use ($organization): void {
            $verdict = $this->getSubscriptionAccess->handle($organization);

            // The unlock exists only for organizations that cannot act;
            // one grant per lifetime...
            if (! $verdict['readOnly'] || $verdict['unlockedUntil'] !== null) {
                throw new OrganizationReadOnly(__('statuses.unlock_used'));
            }

            $organization->forceFill([
                'access_unlock_until' => now()->addHours(24),
            ])->save();
        });

        $this->flushCache->handle($organization->id);
    }
}
