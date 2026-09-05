<?php

declare(strict_types=1);

use App\Actions\CreateClient;
use App\Actions\GenerateClientNumber;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\User;
use App\ValueObjects\Address;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

it('retries the next number when a concurrent create claims the same one', function (): void {
    $action = resolve(CreateClient::class);

    // Simulate the race: another request commits the individual-0001 the
    // generator would produce right as this create starts, so the first
    // insert collides on the composite unique and the action must
    // recompute and succeed with the incremented number...
    $racer = Client::factory()->forOrganization($this->user->organization)->create([
        'clientNumber' => resolve(GenerateClientNumber::class)->handle(ClientType::CLIENT_INDIVIDUAL, $this->user->organization->id),
    ]);

    $client = $action->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
        'email' => 'jean@example.com',
        'addressStreet' => '1234 Rue Sainte-Catherine',
        'addressCity' => 'Montréal',
        'addressPostalCode' => 'H3G 1M7',
        'addressCountry' => 'Canada',
    ]);

    expect($client->clientNumber)->not->toBe($racer->clientNumber)
        ->and($client->clientNumber)->toBe('individual-'.sprintf('%04d', (int) mb_substr($racer->clientNumber, mb_strlen('individual-')) + 1))
        ->and($client->organization_id)->toBe($racer->organization_id);
});

it('gives up and rethrows once every retry collides with a taken number', function (): void {
    $action = resolve(CreateClient::class);

    $before = Client::query()->count();

    // The generator reads committed rows and failed attempts roll back, so
    // pre-seeding alone can never burn all three attempts (the generator
    // would just yield the next free number). Simulate the true race — a
    // concurrent request claiming the number between the generator's read
    // and the insert — by claiming it from a creating hook on every attempt.
    Client::creating(function (Client $client): bool {
        if ($client->email !== 'exhausted@example.com') {
            return true;
        }

        DB::transaction(function () use ($client): void {
            Client::query()->create([
                'organization_id' => $client->organization_id,
                'clientNumber' => $client->clientNumber,
                'type' => $client->type,
                'lastName' => 'Racer',
                'firstName' => 'Concurrent',
                'address' => $client->address,
                'email' => 'racer@example.com',
            ]);
        });

        return true;
    });

    expect(fn (): Client => $action->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
        'email' => 'exhausted@example.com',
        'addressStreet' => '1234 Rue Sainte-Catherine',
        'addressCity' => 'Montréal',
        'addressPostalCode' => 'H3G 1M7',
        'addressCountry' => 'Canada',
    ]))->toThrow(UniqueConstraintViolationException::class);

    // The failed create leaves no partial row behind: each attempt rolled
    // back to its savepoint and the final rethrow aborted the transaction,
    // racer claims included...
    expect(Client::query()->count())->toBe($before)
        ->and(Client::query()->where('email', 'exhausted@example.com')->exists())->toBeFalse()
        ->and(Client::query()->where('email', 'racer@example.com')->exists())->toBeFalse();
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

it('skips the limit check when the organization has an unlimited plan', function (): void {
    // The pro plan has no client ceiling...
    $this->user->organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_create_client_unlimited',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    Client::factory()->count(30)->forOrganization($this->user->organization)->create();

    $client = resolve(CreateClient::class)->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Au-delà',
        'lastName' => 'De la limite',
        'email' => 'beyond@example.com',
        'addressStreet' => '1 Rue Sans Limite',
        'addressCity' => 'Québec',
        'addressPostalCode' => 'G1R 1R1',
        'addressCountry' => 'CA',
    ]);

    expect($client)->toBeInstanceOf(Client::class);
});

it('creates a client without plan enforcement when unauthenticated', function (): void {
    Auth::logout();

    expect(fn () => resolve(CreateClient::class)->handle([
        'type' => ClientType::CLIENT_INDIVIDUAL,
        'firstName' => 'Console',
        'lastName' => 'Context',
        'email' => 'console@example.com',
        'addressStreet' => '1 Rue Console',
        'addressCity' => 'Québec',
        'addressPostalCode' => 'G1R 1R1',
        'addressCountry' => 'CA',
    ]))->toThrow(Exception::class);
});
