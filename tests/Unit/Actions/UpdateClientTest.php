<?php

declare(strict_types=1);

use App\Actions\UpdateClient;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\User;

// The organization scope requires an authenticated user to see any data;
// records must belong to that user's organization to be visible...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('updates a client and rebuilds the address value object', function (): void {
    $client = Client::factory()->individual()->create();
    $action = resolve(UpdateClient::class);

    $action->handle($client, [
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => $client->firstName,
        'lastName' => $client->lastName,
        'email' => 'updated@example.com',
        'phoneDay' => null,
        'cellPhone' => '438 555-0100',
        'addressStreet' => '999 Nouvelle Rue',
        'addressCity' => 'Laval',
        'addressPostalCode' => 'H7V 1A1',
        'addressCountry' => 'Canada',
        'remark' => 'Gate code 1234.',
    ]);

    expect($client->refresh())
        ->email->toBe('updated@example.com')
        ->phoneDay->toBeNull()
        ->cellPhone->toBe('438 555-0100')
        ->remark->toBe('Gate code 1234.')
        ->and($client->address->street)->toBe('999 Nouvelle Rue')
        ->and($client->address->city)->toBe('Laval');
});

it('clears the company name when the type changes to individual', function (): void {
    $client = Client::factory()->company()->create();
    $action = resolve(UpdateClient::class);

    $action->handle($client, [
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => $client->firstName,
        'lastName' => $client->lastName,
        'email' => $client->email,
        'phoneDay' => $client->phoneDay,
        'addressStreet' => $client->address->street,
        'addressCity' => $client->address->city,
        'addressPostalCode' => $client->address->postalCode,
        'addressCountry' => $client->address->country,
    ]);

    expect($client->refresh()->companyName)->toBeNull();
});
