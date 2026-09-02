<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransferOrganizationOwnership;
use App\Http\Requests\TransferOrganizationOwnershipRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final readonly class TransferOrganizationOwnershipController
{
    public function __invoke(
        TransferOrganizationOwnershipRequest $request,
        TransferOrganizationOwnership $action,
    ): RedirectResponse {
        Gate::authorize('transfer-organization', $request->user()->organization);

        $newOwner = User::query()->findOrFail($request->validated('user_id'));

        $action->handle($request->user()->organization, $newOwner);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.organization_transferred'),
        ]);

        return to_route('organization.edit');
    }
}
