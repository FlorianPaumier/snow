<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class TransferOrganizationOwnership
{
    public function handle(Organization $organization, User $newOwner): void
    {
        DB::transaction(function () use ($organization, $newOwner): void {
            // The owner is always an administrator; promoting the target
            // keeps that invariant regardless of their previous role...
            $newOwner->update(['role' => UserRole::ROLE_ADMIN]);

            $organization->update(['owner_id' => $newOwner->id]);
        });
    }
}
