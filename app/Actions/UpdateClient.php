<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Models\Client;
use App\ValueObjects\Address;
use Illuminate\Validation\ValidationException;

final readonly class UpdateClient
{
    public function __construct(private FlushOrganizationCache $flushCache) {}

    /**
     * @param  array{type: ClientType|string, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes
     */
    public function handle(Client $client, array $attributes): void
    {
        // An anonymized record has no identity left to edit; letting data
        // back in would undo the irreversibility the law requires...
        throw_if(
            $client->isAnonymized(),
            ValidationException::withMessages([
                'client' => __('clients.anonymized_readonly'),
            ]),
        );

        $type = $attributes['type'] instanceof ClientType
            ? $attributes['type']
            : ClientType::from($attributes['type']);

        // The client number is assigned at creation and never renumbered,
        // even when the type changes...
        $companyName = null;

        if ($type === ClientType::CLIENT_COMPANY) {
            $companyName = $attributes['companyName'] ?? $client->companyName;
        }

        $client->update([
            'type' => $type,
            'companyName' => $companyName,
            'lastName' => $attributes['lastName'],
            'firstName' => $attributes['firstName'],
            'address' => new Address(
                street: $attributes['addressStreet'],
                city: $attributes['addressCity'],
                postalCode: $attributes['addressPostalCode'],
                country: $attributes['addressCountry'],
            ),
            'phoneDay' => $attributes['phoneDay'] ?? null,
            'cellPhone' => $attributes['cellPhone'] ?? null,
            'email' => $attributes['email'],
            'remark' => $attributes['remark'] ?? null,
        ]);

        $this->flushCache->handle($client->organization_id);
    }
}
