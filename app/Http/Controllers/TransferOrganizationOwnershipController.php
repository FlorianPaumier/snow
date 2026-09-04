<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransferOrganizationOwnership;
use App\Http\Requests\TransferOrganizationOwnershipRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final readonly class TransferOrganizationOwnershipController
{
    public function __invoke(
        TransferOrganizationOwnershipRequest $request,
        #[CurrentUser] User $user,
        TransferOrganizationOwnership $action,
    ): RedirectResponse {
        Gate::authorize('transfer-organization', $user->organization);

        /** @var array{user_id: string} $validated */
        $validated = $request->validated();

        $newOwner = User::query()->findOrFail($validated['user_id']);

        $action->handle($user->organization, $newOwner);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.organization_transferred'),
        ]);

        return to_route('organization.edit');
    }
}
