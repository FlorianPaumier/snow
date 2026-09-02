<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Exceptions\OrganizationOwnerProtected;
use App\Models\User;

final readonly class UpdateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        $this->assertOwnerKeptAsAdmin($user, $attributes);

        $emailChanged = isset($attributes['email']) && $user->email !== $attributes['email'];

        $user->update([
            ...$attributes,
            ...($emailChanged ? ['email_verified_at' => null] : []),
        ]);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }

    /**
     * The owner is the one administrator who can never be demoted;
     * ownership moves through an explicit transfer instead.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function assertOwnerKeptAsAdmin(User $user, array $attributes): void
    {
        $demoted = isset($attributes['role']) && $attributes['role'] !== UserRole::ROLE_ADMIN->value;

        if ($user->isOwner() && $demoted) {
            throw new OrganizationOwnerProtected(__('statuses.organization_owner_role'));
        }
    }
}
