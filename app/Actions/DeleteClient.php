<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\TourNotification;
use Illuminate\Validation\ValidationException;

final readonly class DeleteClient
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    public function handle(Client $client): void
    {
        throw_if(
            $client->contracts()->exists(),
            ValidationException::withMessages([
                'client' => 'This client has contracts and cannot be deleted.',
            ]),
        );

        $organizationId = $client->organization_id;

        TourNotification::query()->withoutGlobalScopes()->where('organization_id', $client->organization_id)->where('client_id', $client->id)->update(['recipient_key' => null]);
        $client->delete();

        $this->flushCache->handle($organizationId);
    }
}
