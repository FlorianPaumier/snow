<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\ValueObjects\Season;

/**
 * Valid contract payload: one driveway service split across the 4 slots.
 *
 * @return array<string, mixed>
 */
function contractPayload(Client $client, ?string $season = null): array
{
    return [
        'clientId' => $client->id,
        'season' => $season ?? Season::current()->label,
        'services' => [
            ['label' => 'Déneigement entrée', 'amount' => 35000],
            ['label' => 'Déglaçage', 'amount' => 10000],
        ],
        'payments' => [
            'deposit' => ['amount' => 15000, 'dueDate' => null],
            'first' => ['amount' => 10000, 'dueDate' => null],
            'second' => ['amount' => 10000, 'dueDate' => null],
            'balance' => ['amount' => 10000, 'dueDate' => null],
        ],
    ];
}

it('renders the contracts index for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('contract/index')
            ->has('contracts.data')
            ->has('seasons'));
});

it('renders the contracts index for regular staff', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('contract/index'));
});

it('requires authentication', function (): void {
    $this->get(route('contracts.index'))->assertRedirect(route('login'));
});

it('filters contracts by season', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    Contract::factory()->forClient($client)->create([
        'season' => Season::current()->label,
    ]);
    Contract::factory()->forClient($client)->create([
        'season' => Season::current()->next()->label,
    ]);

    $this->actingAs($admin)
        ->get(route('contracts.index', ['season' => Season::current()->label]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.data.0.season', Season::current()->label));
});

it('filters contracts by client', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $other = Client::factory()->forOrganization($admin->organization)->create();

    Contract::factory()->forClient($client)->create();
    Contract::factory()->forClient($other)->create();

    $this->actingAs($admin)
        ->get(route('contracts.index', ['client' => $client->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.data.0.client.id', $client->id));
});

it('searches contracts by client name or number', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->individual()->create([
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
    ]);
    $other = Client::factory()->forOrganization($admin->organization)->individual()->create([
        'firstName' => 'Marie',
        'lastName' => 'Roy',
    ]);

    Contract::factory()->forClient($client)->create();
    Contract::factory()->forClient($other)->create();

    $this->actingAs($admin)
        ->get(route('contracts.index', ['search' => 'jean']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.data.0.client.id', $client->id));

    $this->actingAs($admin)
        ->get(route('contracts.index', ['search' => $client->clientNumber]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.data.0.client.id', $client->id));
});

it('paginates contracts', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    foreach (range(1, 16) as $index) {
        Contract::factory()->forClient($client)->create([
            'season' => sprintf('%d-%d', 2000 + $index, 2001 + $index),
        ]);
    }

    $response = $this->actingAs($admin)
        ->get(route('contracts.index', ['page' => 2]))
        ->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->where('contracts.current_page', 2)
        ->where('contracts.per_page', 15)
        ->where('contracts.total', 16)
        ->has('contracts.data', 1));
});

it('prevents non-admins from managing contracts', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();

    $this->actingAs($user)->get(route('contracts.create'))->assertForbidden();
    $this->actingAs($user)->post(route('contracts.store'), [])->assertForbidden();
    $this->actingAs($user)->get(route('contracts.edit', $contract))->assertForbidden();
    $this->actingAs($user)->put(route('contracts.update', $contract), [])->assertForbidden();
    $this->actingAs($user)->delete(route('contracts.destroy', $contract))->assertForbidden();

    // Staff can still view...
    $this->actingAs($user)->get(route('contracts.show', $contract))->assertOk();
});

it('creates a contract with services and the four payment slots', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $response = $this->actingAs($admin)
        ->post(route('contracts.store'), contractPayload($client))
        ->assertRedirect();

    $contract = Contract::query()->where('client_id', $client->id)->first();
    expect($contract)->not->toBeNull()
        ->and($contract->season)->toBe(Season::current()->label);

    // Positions derive from the submitted order...
    expect($contract->services->pluck('label')->all())->toBe(['Déneigement entrée', 'Déglaçage'])
        ->and($contract->services->pluck('position')->all())->toBe([1, 2]);

    // All four slots exist, unpaid (sorted for a stable comparison)...
    $payments = $contract->payments->mapWithKeys(fn (Payment $payment): array => [$payment->slot->value => $payment->amount])->sortKeys()->all();
    expect($payments)->toBe(['balance' => 10000, 'deposit' => 15000, 'first' => 10000, 'second' => 10000])
        ->and($contract->payments->every(fn (Payment $payment): bool => $payment->paid === false))->toBeTrue();

    $response->assertRedirect(route('contracts.show', ['contract' => $contract->id]));
});

it('rejects a duplicate contract for the same client and season on create', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    Contract::factory()->forClient($client)->create(['season' => Season::current()->label,
    ]);

    $this->actingAs($admin)
        ->post(route('contracts.store'), contractPayload($client))
        ->assertSessionHasErrors('season');
});

it('persists optional installment due dates on create and update', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->post(route('contracts.store'), [
            ...contractPayload($client),
            'payments' => [
                'deposit' => ['amount' => 15000, 'dueDate' => '2026-11-01'],
                'first' => ['amount' => 10000, 'dueDate' => '2026-12-01'],
                'second' => ['amount' => 10000, 'dueDate' => null],
                'balance' => ['amount' => 10000, 'dueDate' => null],
            ],
        ])
        ->assertRedirect();

    $contract = Contract::query()->where('client_id', $client->id)->first();
    $payments = $contract->payments->keyBy('slot.value');

    expect($payments['deposit']->due_date->toDateString())->toBe('2026-11-01')
        ->and($payments['first']->due_date->toDateString())->toBe('2026-12-01')
        ->and($payments['second']->due_date)->toBeNull();

    $this->actingAs($admin)
        ->put(route('contracts.update', $contract), [
            'clientId' => $client->id,
            'season' => $contract->season,
            'services' => [
                ['label' => 'Déneigement entrée', 'amount' => 35000],
                ['label' => 'Déglaçage', 'amount' => 10000],
            ],
            'payments' => [
                'deposit' => ['amount' => 15000, 'dueDate' => null],
                'first' => ['amount' => 10000, 'dueDate' => '2027-01-15'],
                'second' => ['amount' => 10000, 'dueDate' => null],
                'balance' => ['amount' => 10000, 'dueDate' => null],
            ],
        ])
        ->assertRedirect();

    expect($payments['deposit']->fresh()->due_date)->toBeNull()
        ->and($payments['first']->fresh()->due_date->toDateString())->toBe('2027-01-15');
});

it('validates create payloads', function (array $payload): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->post(route('contracts.store'), $payload)
        ->assertSessionHasErrors();
})->with([
    'missing client' => [['clientId' => null, 'season' => '2026-2027', 'services' => [['label' => 'Entrée', 'amount' => 100]], 'payments' => ['deposit' => ['amount' => 100, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => null], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]]]],
    'client id as array' => [['clientId' => ['not-a-uuid'], 'season' => '2026-2027', 'services' => [['label' => 'Entrée', 'amount' => 100]], 'payments' => ['deposit' => ['amount' => 100, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => null], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]]]],
    'invalid season format' => [['clientId' => '00000000-0000-0000-0000-000000000000', 'season' => 'ete-2026', 'services' => [['label' => 'Entrée', 'amount' => 100]], 'payments' => ['deposit' => ['amount' => 100, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => null], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]]]],
    'empty services' => [['clientId' => '00000000-0000-0000-0000-000000000000', 'season' => '2026-2027', 'services' => [], 'payments' => ['deposit' => ['amount' => 0, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => null], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]]]],
    'unbalanced payments' => [['clientId' => '00000000-0000-0000-0000-000000000000', 'season' => '2026-2027', 'services' => [['label' => 'Entrée', 'amount' => 400]], 'payments' => ['deposit' => ['amount' => 100, 'dueDate' => null], 'first' => ['amount' => 100, 'dueDate' => null], 'second' => ['amount' => 100, 'dueDate' => null], 'balance' => ['amount' => 50, 'dueDate' => null]]]],
    'negative amount' => [['clientId' => '00000000-0000-0000-0000-000000000000', 'season' => '2026-2027', 'services' => [['label' => 'Entrée', 'amount' => -1]], 'payments' => ['deposit' => ['amount' => 0, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => null], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]]]],
    'missing slot' => [['clientId' => '00000000-0000-0000-0000-000000000000', 'season' => '2026-2027', 'services' => [['label' => 'Entrée', 'amount' => 100]], 'payments' => ['deposit' => 100, 'first' => 0, 'second' => 0]]],
    'invalid due date' => [['clientId' => '00000000-0000-0000-0000-000000000000', 'season' => '2026-2027', 'services' => [['label' => 'Entrée', 'amount' => 100]], 'payments' => ['deposit' => ['amount' => 100, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => 'not-a-date'], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]]]],
]);

it('renders the edit page with prefilled services and payments', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->createMany([
        ['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1],
    ]);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => false,
        'method' => null,
        'paid_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('contracts.edit', $contract))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('contract/edit')
            ->where('contract.id', $contract->id)
            ->where('contract.clientId', $client->id)
            ->has('contract.services', 1)
            ->has('contract.payments.deposit'));
});

it('updates a contract syncing services and unpaid amounts', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $kept = $contract->services()->create(['label' => 'Déneigement entrée', 'amount' => 40000, 'position' => 1]);
    $removed = $contract->services()->create(['label' => 'Déglaçage', 'amount' => 5000, 'position' => 2]);
    $deposit = $contract->payments()->create(['slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => false, 'method' => null, 'paid_at' => null]);
    $first = $contract->payments()->create(['slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 30000, 'paid' => false, 'method' => null, 'paid_at' => null]);

    $this->actingAs($admin)
        ->put(route('contracts.update', $contract), [
            'clientId' => $client->id,
            'season' => $contract->season,
            'services' => [
                ['id' => $kept->id, 'label' => 'Déneigement entrée', 'amount' => 35000],
                ['label' => 'Stationnement', 'amount' => 5000],
            ],
            'payments' => [
                'deposit' => ['amount' => 20000, 'dueDate' => null],
                'first' => ['amount' => 20000, 'dueDate' => null],
                'second' => ['amount' => 0, 'dueDate' => null],
                'balance' => ['amount' => 0, 'dueDate' => null],
            ],
        ])
        ->assertRedirect(route('contracts.show', $contract));

    $contract->refresh();

    // Kept row updated, removed row gone, new row created; positions re-derived...
    expect($contract->services->pluck('label')->all())->toBe(['Déneigement entrée', 'Stationnement'])
        ->and($contract->services->count())->toBe(2)
        ->and($contract->services->pluck('position')->all())->toBe([1, 2])
        ->and($removed->fresh())->toBeNull();

    expect($deposit->fresh()->amount)->toBe(20000)
        ->and($first->fresh()->amount)->toBe(20000);
});

it('locks paid installment amounts on update', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->create(['label' => 'Entrée', 'amount' => 40000, 'position' => 1]);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_VIREMENT,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->put(route('contracts.update', $contract), [
            'clientId' => $client->id,
            'season' => $contract->season,
            'services' => [['label' => 'Entrée', 'amount' => 40000]],
            'payments' => [
                'deposit' => ['amount' => 10000, 'dueDate' => null], // Different from the stored 15000...
                'first' => ['amount' => 25000, 'dueDate' => null],
                'second' => ['amount' => 0, 'dueDate' => null],
                'balance' => ['amount' => 0, 'dueDate' => null],
            ],
        ])
        ->assertSessionHasErrors('payments.deposit.amount');
});

it('rejects a duplicate season on update', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $contract = Contract::factory()->forClient($client)->create(['season' => Season::current()->label,
    ]);
    Contract::factory()->forClient($client)->create(['season' => Season::current()->previous()->label,
    ]);

    $this->actingAs($admin)
        ->put(route('contracts.update', $contract), contractPayload($client, Season::current()->previous()->label))
        ->assertSessionHasErrors('season');
});

it('deletes a contract without paid installments', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->create(['label' => 'Entrée', 'amount' => 40000, 'position' => 1]);
    $contract->payments()->create(['slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 40000, 'paid' => false, 'method' => null, 'paid_at' => null]);

    $this->actingAs($admin)
        ->delete(route('contracts.destroy', $contract))
        ->assertRedirect(route('contracts.index'));

    expect($contract->fresh())->toBeNull();
});

it('refuses to delete a contract with paid installments', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_CASH,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('contracts.destroy', $contract))
        ->assertSessionHasErrors('contract');

    expect($contract->fresh())->not->toBeNull();
});

it('renders the show page with services, payments and totals', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->services()->createMany([
        ['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1],
        ['label' => 'Stationnement', 'amount' => 15000, 'position' => 2],
    ]);
    $contract->payments()->createMany([
        ['slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => true, 'method' => PaymentMethod::PAYMENT_VIREMENT, 'paid_at' => now()],
        ['slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 42500, 'paid' => false, 'method' => null, 'paid_at' => null],
    ]);

    $this->actingAs($user)
        ->get(route('contracts.show', $contract))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('contract/show')
            ->where('contract.id', $contract->id)
            ->where('contract.client.displayName', sprintf('%s %s', $client->firstName, $client->lastName))
            ->has('contract.services', 2)
            ->has('contract.payments', 2)
            ->where('contract.total', 57500)
            ->where('contract.collected', 15000)
            ->where('contract.outstanding', 42500)
            ->has('methods'));
});

it('renders the contract creation page with client, season and slot options', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->company()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->get(route('contracts.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('contract/create')
            ->has('clients')
            ->has('seasons')
            ->has('slots'));
});

it('skips the totals cross-check when the update payload shapes are malformed', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();

    // Non-array services already fail the declared rules; the totals
    // cross-check must bow out instead of crashing on the shape...
    $this->actingAs($admin)
        ->put(route('contracts.update', $contract), [
            'clientId' => $client->id,
            'season' => $contract->season,
            'services' => 'not-an-array',
            'payments' => ['deposit' => ['amount' => 0, 'dueDate' => null], 'first' => ['amount' => 0, 'dueDate' => null], 'second' => ['amount' => 0, 'dueDate' => null], 'balance' => ['amount' => 0, 'dueDate' => null]],
        ])->assertSessionHasErrors('services');
});

it('displays company names as the client display name in the index', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->company()->forOrganization($admin->organization)->create(['companyName' => 'Déneigement Nordique Inc.']);
    Contract::factory()->forClient($client)->create();

    $this->actingAs($admin)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('contracts.data.0.client.displayName', 'Déneigement Nordique Inc.'));
});
