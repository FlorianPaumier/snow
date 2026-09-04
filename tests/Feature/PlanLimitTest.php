<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

it('blocks creating a second user on the free plan', function (): void {
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    $response = $this->actingAs($admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'role' => 'user',
        ]);

    $response->assertRedirectToRoute('users.create')
        ->assertSessionHas(
            'status',
            __('statuses.plan_limit', [
                'plan' => 'free',
                'count' => 1,
                'resource' => __('statuses.resource_users'),
            ]),
        );

    expect(User::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(1);
});

it('allows creating users on the pro plan without a limit', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    // Pro has no user limit; create several beyond the free cap...
    User::factory()->count(5)->forOrganization($organization)->create();

    expect(User::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(6);
});

it('allows unlimited users while the signup trial runs', function (): void {
    $organization = Organization::factory()->create(); // with trial...
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    // The trial carries the full Pro plan: no cap to hit...
    User::factory()->count(3)->forOrganization($organization)->create();

    expect(User::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(4);
});

it('blocks creating clients beyond the free plan limit', function (): void {
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    // Use the model directly to fill the quota without hitting the limit
    // check itself...
    Client::factory()->count(15)->forOrganization($organization)->create();

    $response = $this->actingAs($admin)
        ->fromRoute('clients.create')
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Over',
            'lastName' => 'Limit',
            'email' => 'over@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ]);

    $response->assertRedirectToRoute('clients.create')
        ->assertSessionHas(
            'status',
            __('statuses.plan_limit', [
                'plan' => 'free',
                'count' => 15,
                'resource' => __('statuses.resource_clients'),
            ]),
        );

    expect(Client::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(15);
});

it('keeps an expired-trial organization writable below the free caps', function (): void {
    // The flagship behavior of the free model: the trial ending no
    // longer locks the organization — writes keep working under the
    // free caps...
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    $this->actingAs($admin)
        ->fromRoute('clients.create')
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Still',
            'lastName' => 'Writable',
            'email' => 'still.writable@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ])->assertRedirect(route('clients.index'));

    expect(Client::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(1);
});

it('does not count anonymized clients against the plan limit', function (): void {
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    Client::factory()->count(14)->forOrganization($organization)->create();
    // Anonymized skeletons are kept for accountability but must not
    // consume the organization's quota...
    Client::factory()->count(3)->forOrganization($organization)->create(['anonymized_at' => now()]);

    $this->actingAs($admin)
        ->fromRoute('clients.create')
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Still',
            'lastName' => 'Allowed',
            'email' => 'still.allowed@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ])
        ->assertRedirect(route('clients.index'));

    expect(Client::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->whereNull('anonymized_at')->count())->toBe(15);
});
