<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\OrganizationOwnerProtected;
use App\Models\User;

final readonly class DeleteUser
{
    public function handle(User $user): void
    {
        // Ownership must move to another member before the account can go...
        throw_if($user->isOwner(), new OrganizationOwnerProtected(__('statuses.organization_owner_delete')));

        $user->delete();
    }
}
