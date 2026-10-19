<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClientRouteNotificationRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;

final readonly class ClientRouteNotificationController
{
    public function update(UpdateClientRouteNotificationRequest $request, Client $client): RedirectResponse
    {
        abort_if($client->isAnonymized(), 410);
        $client->forceFill(['route_notifications_enabled' => $request->boolean('enabled')])->save();

        return to_route('clients.show', $client)->with('status', __('tour_notifications.preference_saved'));
    }
}
