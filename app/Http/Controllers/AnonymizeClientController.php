<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnonymizeClient;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class AnonymizeClientController
{
    /**
     * Fulfil a personal-data erasure/anonymization request (Quebec Law 25
     * s. 22.1): identity fields are irreversibly erased, the financial
     * skeleton stays for accountability.
     */
    public function __invoke(Request $request, Client $client, AnonymizeClient $action): RedirectResponse
    {
        $action->handle($client, $request->user());

        return to_route('clients.show', ['client' => $client->id])
            ->with('status', __('statuses.client_anonymized'));
    }
}
