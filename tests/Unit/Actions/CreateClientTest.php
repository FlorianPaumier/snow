<?php

declare(strict_types=1);

use App\Actions\CreateClient;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\User;
use App\ValueObjects\Address;
use Illuminate\Support\Facades\Auth;

// Creating a client stamps the acting user's organization; the action
// requires an authenticated context...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('may create an individual client with a generated client number', function (): void {
    $action = resolve(CreateClient::class);

    $client = $action->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
        'email' => 'jean@example.com',
        'phoneDay' => '514 555-0100',
        'addressStreet' => '1234 Rue Sainte-Catherine',
        'addressCity' => 'Montréal',
        'addressPostalCode' => 'H3G 1M7',
        'addressCountry' => 'Canada',
    ]);

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->clientNumber)->toBe('individual-0001')
        ->and($client->type)->toBe(ClientType::CLIENT_INDIVIDUAL)
        ->and($client->firstName)->toBe('Jean')
        ->and($client->companyName)->toBeNull()
        ->and($client->phoneDay)->toBe('514 555-0100')
        ->and($client->cellPhone)->toBeNull()
        ->and($client->address)->toBeInstanceOf(Address::class)
        ->and($client->address->street)->toBe('1234 Rue Sainte-Catherine')
        ->and($client->address->city)->toBe('Montréal');

    // The address value object round-trips through the underlying columns...
    $fresh = Client::query()->find($client->id);
    expect($fresh->address->postalCode)->toBe('H3G 1M7')
        ->and($fresh->address->country)->toBe('Canada');
});

it('may create a company client with an ember... in its own sequence', function (): void {
    $action = resolve(CreateClient::class);

    $individual = $action->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
        'email' => 'jean@example.com',
        'phoneDay' => '514 555-0100',
        'addressStreet' => '1234 Rue Sainte-Catherine',
        'addressCity' => 'Montréal',
        'addressPostalCode' => 'H3G 1M7',
        'addressCountry' => 'Canada',
    ]);

    $company = $action->handle([
        'type' => ClientType::CLIENT_COMPANY,
        'companyName' => 'Gestion Tremblay Inc.',
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
        'email' => 'contact@gestiontremblay.com',
        'phoneDay' => '514 555-0100',
        'addressStreet' => '1 Avenue des Canadiens',
        'addressCity' => 'Montréal',
        'addressPostalCode' => 'H4B 1L1',
        'addressCountry' => 'Canada',
    ]);

    expect($individual->clientNumber)->toBe('individual-0001')
        ->and($company->clientNumber)->toBe('company-0001')
        ->and($company->companyName)->toBe('Gestion Tremblay Inc.');
});
