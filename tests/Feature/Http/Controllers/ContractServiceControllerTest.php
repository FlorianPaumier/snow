<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Str;

/**
 * Edge cases of the contract payload rules (amount ceilings, label length,
 * array shape, missing slots) that complement the create/update datasets in
 * ContractControllerTest.
 */
function edgeCasePayload(Client $client, array $overrides = []): array
{
    return [
        'clientId' => $client->id,
        'season' => Season::current()->label,
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 35000],
            ['label' => 'Déglaçage', 'amount' => 10000],
        ],
        'payments' => [
            'deposit' => 15000,
            'first' => 10000,
            'second' => 10000,
            'balance' => 10000,
        ],
        ...$overrides,
    ];
}

it('rejects a service label longer than 255 characters', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $payload = edgeCasePayload($client, [
        'services' => [
            ['label' => Str::repeat('a', 256), 'amount' => 35000],
        ],
        'payments' => ['deposit' => 35000, 'first' => 0, 'second' => 0, 'balance' => 0],
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors('services.0.label');
});

it('rejects a service amount above the ceiling', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $payload = edgeCasePayload($client, [
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 10000001],
        ],
        'payments' => ['deposit' => 10000001, 'first' => 0, 'second' => 0, 'balance' => 0],
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors(['services.0.amount', 'payments.deposit']);
});

it('rejects a non-array services payload', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $payload = edgeCasePayload($client, [
        'services' => 'not-an-array',
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors('services');
});

it('rejects a payment below zero', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $payload = edgeCasePayload($client, [
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 30000],
        ],
        'payments' => ['deposit' => 30000, 'first' => -1, 'second' => 0, 'balance' => 1],
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors('payments.first');
});

it('rejects a missing payment slot', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $payload = edgeCasePayload($client, [
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 30000],
        ],
        'payments' => ['deposit' => 30000, 'first' => 0, 'second' => 0],
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors('payments.balance');
});

it('rejects a non-uuid client id', function (): void {
    $admin = User::factory()->admin()->create();

    $payload = edgeCasePayload(new Client, [
        'clientId' => 'not-a-uuid',
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors('clientId');
});
