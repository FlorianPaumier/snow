<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    $this->organizationA = Organization::factory()->create(['name' => 'Org A']);
    $this->organizationB = Organization::factory()->create(['name' => 'Org B']);

    $this->adminA = User::factory()->admin()->forOrganization($this->organizationA)->create();

    // A full data set in organization B that organization A must never see...
    $this->adminB = User::factory()->admin()->forOrganization($this->organizationB)->create();
    $this->workerB = User::factory()->worker()->forOrganization($this->organizationB)->create();
    $this->clientB = Client::factory()->forOrganization($this->organizationB)->create();
});

it('lists only users of the current organization', function (): void {
    $this->actingAs($this->adminA)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(function ($page): void {
            $page->component('user-management/index')
                ->has('users.data', 1)
                ->where('users.data.0.id', $this->adminA->id);
        });
});

it('lists only clients of the current organization', function (): void {
    $this->actingAs($this->adminA)
        ->get(route('clients.index'))
        ->assertOk();

    // Sanity check via the model layer, since the client index paginates...
    expect(Client::query()->count())->toBe(0)
        ->and(Client::query()->withoutGlobalScopes()->where('organization_id', $this->organizationB->id)->count())->toBe(1);
});

it('does not find another organizations user on edit', function (): void {
    $this->actingAs($this->adminA)
        ->get(route('users.edit', $this->workerB))
        ->assertNotFound();
});

it('cannot update another organizations user', function (): void {
    $this->actingAs($this->adminA)
        ->put(route('users.update', $this->workerB), [
            'name' => 'Hijacked',
            'email' => 'hijacked@example.com',
            'role' => 'admin',
        ])
        ->assertNotFound();

    expect($this->workerB->refresh()->name)->not->toBe('Hijacked');
});

it('cannot delete another organizations user', function (): void {
    $this->actingAs($this->adminA)
        ->delete(route('users.destroy', $this->workerB))
        ->assertNotFound();

    $this->assertModelExists($this->workerB);
});

it('does not find another organizations client on show', function (): void {
    $this->actingAs($this->adminA)
        ->get(route('clients.show', $this->clientB))
        ->assertNotFound();
});

it('cannot update another organizations client', function (): void {
    $this->actingAs($this->adminA)
        ->put(route('clients.update', $this->clientB), [])
        ->assertNotFound();

    $this->assertModelExists($this->clientB);
});

it('cannot delete another organizations client', function (): void {
    $this->actingAs($this->adminA)
        ->delete(route('clients.destroy', $this->clientB))
        ->assertNotFound();

    $this->assertModelExists($this->clientB);
});

it('stamps the organization on newly created clients', function (): void {
    $this->actingAs($this->adminA)
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'New',
            'lastName' => 'Client',
            'email' => 'new@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ]);

    expect($this->organizationA->clients()->count())->toBe(1);
});

it('returns all rows for unauthenticated internal contexts', function (): void {
    // Simulates a job or command without an authenticated user: internal
    // code is trusted and pre-auth flows (login, password reset) must be
    // able to resolve users globally...
    expect(Client::query()->count())->toBe(1)
        ->and(Client::query()->first()->organization_id)->toBe($this->organizationB->id);
});

it('resolves the session user without recursing', function (): void {
    // actingAs() caches the user on the guard and never exercises session
    // retrieval. A real request resolves the user from the session through
    // a User query, which applies this scope; before the hasUser() guard
    // that looped forever (Xdebug aborted at 512 stack frames). The
    // retrieval is keyed by the server-side session id, so leaving it
    // unscoped is safe...
    Auth::guard('web')->login($this->adminA);

    // Simulate the next request: the id is in the session, but the guard
    // starts without a cached user and must fetch it through the scope...
    Auth::guard('web')->forgetUser();

    $this->get(route('dashboard'))
        ->assertOk();

    // Scoping resumes once the user is resolved: only org A's users...
    expect(User::query()->count())->toBe(1);
});
