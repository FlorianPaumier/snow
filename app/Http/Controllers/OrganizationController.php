<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateOrganization;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrganizationController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        $organization = $user->organization;

        return Inertia::render('organization/edit', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'ownerId' => $organization->owner_id,
                'ownerName' => $organization->owner?->name,
            ],
            'members' => $organization->users()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, #[CurrentUser] User $user, UpdateOrganization $action): RedirectResponse
    {
        /** @var array{name: string} $validated */
        $validated = $request->validated();

        $action->handle($user->organization, $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.organization_updated'),
        ]);

        return back();
    }
}
