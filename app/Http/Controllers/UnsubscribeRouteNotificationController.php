<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UnsubscribeRouteNotificationController
{
    public function show(Request $request, string $organization, string $client): Response
    {
        $recipient = $this->resolve($organization, $client);

        return Inertia::render('service-updates/unsubscribe', ['organization' => $organization, 'client' => $client,
            'organizationName' => $recipient->organization->name, 'enabled' => $recipient->route_notifications_enabled,
            'signature' => $request->query('signature')]);
    }

    public function store(Request $request, string $organization, string $client): RedirectResponse
    {
        $this->resolve($organization, $client)->forceFill(['route_notifications_enabled' => false])->save();

        return redirect($request->fullUrl());
    }

    private function resolve(string $organization, string $client): Client
    {
        return Client::query()->withoutGlobalScopes()->where('organization_id', $organization)->whereNotAnonymized()->findOrFail($client);
    }
}
