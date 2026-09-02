<?php

declare(strict_types=1);

use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('renders the clients index for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/index')
            ->has('clients.data')
            ->where('filters.sort', 'created_at')
            ->where('filters.direction', 'desc'));
});

it('renders the clients index for regular staff', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('clients/index'));
});

it('requires authentication', function (): void {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

it('searches clients by name, company, or number', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->individual()->create([
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
        'email' => 'jean@example.com',
    ]);
    Client::factory()->forOrganization($admin->organization)->company()->create(['companyName' => 'Gestion Tremblay']);
    Client::factory()->forOrganization($admin->organization)->individual()->create([
        'firstName' => 'Marie',
        'lastName' => 'Roy',
        'email' => 'marie@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('clients.index', ['search' => 'jean']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.firstName', 'Jean'));

    $this->actingAs($admin)
        ->get(route('clients.index', ['search' => 'gestion']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.companyName', 'Gestion Tremblay'));

    $this->actingAs($admin)
        ->get(route('clients.index', ['search' => 'individual-0001']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.clientNumber', 'individual-0001'));
});

it('sorts clients by the requested column and direction', function (): void {
    $admin = User::factory()->admin()->create();
    Client::factory()->forOrganization($admin->organization)->individual()->create(['lastName' => 'Zed']);
    Client::factory()->forOrganization($admin->organization)->individual()->create(['lastName' => 'Anna']);

    $this->actingAs($admin)
        ->get(route('clients.index', ['sort' => 'lastName', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.data.0.lastName', 'Anna')
            ->where('clients.data.1.lastName', 'Zed'));

    $this->actingAs($admin)
        ->get(route('clients.index', ['sort' => 'lastName', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.data.0.lastName', 'Zed'));
});

it('rejects invalid sort parameters', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('clients.index', ['sort' => 'password', 'direction' => 'sideways']))
        ->assertSessionHasErrors(['sort', 'direction']);
});

it('paginates clients', function (): void {
    $admin = User::factory()->admin()->create();

    // Created one at a time so each factory instance derives its client
    // number from the database after the previous insert...
    foreach (range(1, 20) as $unused) {
        Client::factory()->forOrganization($admin->organization)->create();
    }

    $response = $this->actingAs($admin)
        ->get(route('clients.index', ['page' => 2]))
        ->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->where('clients.current_page', 2)
        ->where('clients.per_page', 15)
        ->where('clients.total', 20)
        ->has('clients.data', 5));
});

it('prevents non-admins from managing clients', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();

    $this->actingAs($user)->get(route('clients.create'))->assertForbidden();
    $this->actingAs($user)->post(route('clients.store'), [])->assertForbidden();
    $this->actingAs($user)->get(route('clients.edit', $client))->assertForbidden();
    $this->actingAs($user)->put(route('clients.update', $client), [])->assertForbidden();
    $this->actingAs($user)->delete(route('clients.destroy', $client))->assertForbidden();

    // Staff can still view...
    $this->actingAs($user)->get(route('clients.show', $client))->assertOk();
});

it('creates an individual client with a sequential client number', function (): void {
    Event::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('clients.store'), [
            'type' => ClientType::CLIENT_INDIVIDUAL->value,
            'firstName' => 'Jean',
            'lastName' => 'Tremblay',
            'email' => 'jean@example.com',
            'phoneDay' => '514 555-0100',
            'addressStreet' => '1234 Rue Sainte-Catherine',
            'addressCity' => 'Montréal',
            'addressPostalCode' => 'H3G 1M7',
            'addressCountry' => 'Canada',
        ])
        ->assertRedirect(route('clients.index'));

    $client = Client::query()->where('email', 'jean@example.com')->first();
    expect($client)->not->toBeNull()
        ->and($client->clientNumber)->toBe('individual-0001')
        ->and($client->companyName)->toBeNull()
        ->and($client->address->city)->toBe('Montréal');

    $this->actingAs($admin)
        ->post(route('clients.store'), [
            'type' => ClientType::CLIENT_INDIVIDUAL->value,
            'firstName' => 'Marie',
            'lastName' => 'Roy',
            'email' => 'marie@example.com',
            'cellPhone' => '438 555-0100',
            'addressStreet' => '500 Rue Sherbrooke',
            'addressCity' => 'Montréal',
            'addressPostalCode' => 'H2L 1L1',
            'addressCountry' => 'Canada',
        ])
        ->assertRedirect(route('clients.index'));

    expect(Client::query()->where('email', 'marie@example.com')->first()->clientNumber)
        ->toBe('individual-0002');
});

it('creates a company client with its own sequence', function (): void {
    Event::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('clients.store'), [
            'type' => ClientType::CLIENT_COMPANY->value,
            'companyName' => 'Gestion Tremblay Inc.',
            'firstName' => 'Jean',
            'lastName' => 'Tremblay',
            'email' => 'contact@gestiontremblay.com',
            'phoneDay' => '514 555-0100',
            'addressStreet' => '1 Avenue des Canadiens',
            'addressCity' => 'Montréal',
            'addressPostalCode' => 'H4B 1L1',
            'addressCountry' => 'Canada',
        ])
        ->assertRedirect(route('clients.index'));

    $client = Client::query()->where('email', 'contact@gestiontremblay.com')->first();
    expect($client)->not->toBeNull()
        ->and($client->clientNumber)->toBe('company-0001')
        ->and($client->companyName)->toBe('Gestion Tremblay Inc.');
});

it('does not reuse client numbers after deletion', function (): void {
    Event::fake();
    $admin = User::factory()->admin()->create();

    // Two clients exist; deleting the first must not reissue its number...
    $first = Client::factory()->forOrganization($admin->organization)->individual()->create();
    Client::factory()->forOrganization($admin->organization)->individual()->create();
    $first->delete();

    $this->actingAs($admin)
        ->post(route('clients.store'), [
            'type' => ClientType::CLIENT_INDIVIDUAL->value,
            'firstName' => 'Marie',
            'lastName' => 'Roy',
            'email' => 'marie@example.com',
            'phoneDay' => '514 555-0100',
            'addressStreet' => '500 Rue Sherbrooke',
            'addressCity' => 'Montréal',
            'addressPostalCode' => 'H2L 1L1',
            'addressCountry' => 'Canada',
        ])
        ->assertRedirect(route('clients.index'));

    expect(Client::query()->where('email', 'marie@example.com')->first()->clientNumber)
        ->toBe('individual-0003');
});

it('validates the create client payload', function (array $payload): void {
    Event::fake();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('clients.store'), $payload)
        ->assertSessionHasErrors();
})->with([
    'missing names' => [['type' => 'individual', 'email' => 'a@example.com', 'phoneDay' => '514 555-0100', 'addressStreet' => '1 Main St', 'addressCity' => 'Montréal', 'addressPostalCode' => 'H3G 1M7', 'addressCountry' => 'Canada']],
    'invalid email' => [['type' => 'individual', 'firstName' => 'Jean', 'lastName' => 'Tremblay', 'email' => 'not-an-email', 'phoneDay' => '514 555-0100', 'addressStreet' => '1 Main St', 'addressCity' => 'Montréal', 'addressPostalCode' => 'H3G 1M7', 'addressCountry' => 'Canada']],
    'invalid type' => [['type' => 'government', 'firstName' => 'Jean', 'lastName' => 'Tremblay', 'email' => 'a@example.com', 'phoneDay' => '514 555-0100', 'addressStreet' => '1 Main St', 'addressCity' => 'Montréal', 'addressPostalCode' => 'H3G 1M7', 'addressCountry' => 'Canada']],
    'company without name' => [['type' => 'company', 'firstName' => 'Jean', 'lastName' => 'Tremblay', 'email' => 'a@example.com', 'phoneDay' => '514 555-0100', 'addressStreet' => '1 Main St', 'addressCity' => 'Montréal', 'addressPostalCode' => 'H3G 1M7', 'addressCountry' => 'Canada']],
    'no phone at all' => [['type' => 'individual', 'firstName' => 'Jean', 'lastName' => 'Tremblay', 'email' => 'a@example.com', 'addressStreet' => '1 Main St', 'addressCity' => 'Montréal', 'addressPostalCode' => 'H3G 1M7', 'addressCountry' => 'Canada']],
    'missing address' => [['type' => 'individual', 'firstName' => 'Jean', 'lastName' => 'Tremblay', 'email' => 'a@example.com', 'phoneDay' => '514 555-0100']],
]);

it('renders the edit page', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->get(route('clients.edit', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/edit')
            ->where('client.id', $client->id)
            ->where('client.clientNumber', $client->clientNumber));
});

it('updates a client without changing their client number', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->individual()->create();
    $originalNumber = $client->clientNumber;

    $this->actingAs($admin)
        ->put(route('clients.update', $client), [
            'type' => ClientType::CLIENT_INDIVIDUAL->value,
            'firstName' => 'Jean-Updated',
            'lastName' => $client->lastName,
            'email' => $client->email,
            'phoneDay' => $client->phoneDay,
            'addressStreet' => $client->address->street,
            'addressCity' => 'Laval',
            'addressPostalCode' => $client->address->postalCode,
            'addressCountry' => $client->address->country,
        ])
        ->assertRedirect(route('clients.show', $client));

    $client->refresh();
    expect($client->firstName)->toBe('Jean-Updated')
        ->and($client->address->city)->toBe('Laval')
        ->and($client->clientNumber)->toBe($originalNumber);
});

it('nulls the company name when switching to individual', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->company()->create();

    $this->actingAs($admin)
        ->put(route('clients.update', $client), [
            'type' => ClientType::CLIENT_INDIVIDUAL->value,
            'firstName' => $client->firstName,
            'lastName' => $client->lastName,
            'email' => $client->email,
            'phoneDay' => $client->phoneDay,
            'addressStreet' => $client->address->street,
            'addressCity' => $client->address->city,
            'addressPostalCode' => $client->address->postalCode,
            'addressCountry' => $client->address->country,
        ])
        ->assertRedirect(route('clients.show', $client));

    expect($client->refresh()->companyName)->toBeNull();
});

it('deletes a client', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'));

    expect($client->fresh())->toBeNull();
});

it('refuses to delete a client that has contracts', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    Contract::factory()->forClient($client)->create();

    $this->actingAs($admin)
        ->delete(route('clients.destroy', $client))
        ->assertSessionHasErrors('client');

    expect($client->fresh())->not->toBeNull();
});

it('renders the show page with record details', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->company()->create(['companyName' => 'Gestion Tremblay']);
    // One contract per season per client (unique constraint)...
    Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    Contract::factory()->forClient($client)->create(['season' => '2025-2026']);

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/show')
            ->where('client.clientNumber', $client->clientNumber)
            ->where('client.companyName', 'Gestion Tremblay')
            ->where('client.address.city', $client->address->city)
            ->where('client.contractsCount', 2));
});

it('renders the show page with the client contracts and their totals', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $current = Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    $current->services()->createMany([
        ['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1],
        ['label' => 'Stationnement', 'amount' => 15000, 'position' => 2],
    ]);
    $current->payments()->create([
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 15000,
        'paid' => true,
        'method' => PaymentMethod::PAYMENT_VIREMENT,
        'paid_at' => now(),
    ]);
    Contract::factory()->forClient($client)->create(['season' => '2025-2026']);
    // Another organization's contract must never leak into the list...
    Contract::factory()->create(['season' => '2026-2027']);

    $this->actingAs($admin)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('client.contracts', 2)
            // Newest season first...
            ->where('client.contracts.0.id', $current->id)
            ->where('client.contracts.0.season', '2026-2027')
            ->where('client.contracts.0.total', 57500)
            ->where('client.contracts.0.collected', 15000)
            ->where('client.contracts.0.outstanding', 42500)
            ->where('client.contracts.1.season', '2025-2026')
            ->where('client.contracts.1.total', 0));
});

it('renders the client creation page for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('clients.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('clients/create'));
});
