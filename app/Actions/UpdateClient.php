<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Models\Client;
use App\ValueObjects\Address;

final readonly class UpdateClient
{
    /**
     * @param  array{type: ClientType|string, companyName?: string|null, firstName: string, lastName: string, email: string, phoneDay?: string|null, cellPhone?: string|null, addressStreet: string, addressCity: string, addressPostalCode: string, addressCountry: string, remark?: string|null}  $attributes
     */
    public function handle(Client $client, array $attributes): void
    {
        $type = $attributes['type'] instanceof ClientType
            ? $attributes['type']
            : ClientType::from($attributes['type']);

        $client->update([
            'type' => $type,
            // The client number is assigned at creation and never renumbered,
            // even when the type changes...
            'companyName' => $type === ClientType::CLIENT_COMPANY
                ? ($attributes['companyName'] ?? $client->companyName)
                : null,
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
    }
}
