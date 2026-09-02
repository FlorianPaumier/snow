<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateOrganization;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrganizationController
{
    public function edit(Request $request): Response
    {
        $organization = $request->user()->organization;

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

    public function update(UpdateOrganizationRequest $request, UpdateOrganization $action): RedirectResponse
    {
        $action->handle($request->user()->organization, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.organization_updated'),
        ]);

        return back();
    }
}
