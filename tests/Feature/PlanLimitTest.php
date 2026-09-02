<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

it('blocks creating a third user on the starter plan', function (): void {
    // Future trial keeps the org out of read-only enforcement so this test
    // exercises the plan-limit wall in isolation...
    $organization = Organization::factory()->create(['trial_ends_at' => now()->addDays(14)]);
    $admin = User::factory()->admin()->forOrganization($organization)->create();
    User::factory()->forOrganization($organization)->create();

    $response = $this->actingAs($admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), [
            'name' => 'Third User',
            'email' => 'third@example.com',
            'role' => 'user',
        ]);

    $response->assertRedirectToRoute('users.create')
        ->assertSessionHas(
            'status',
            __('statuses.plan_limit', [
                'plan' => 'starter',
                'count' => 2,
                'resource' => __('statuses.resource_users'),
            ]),
        );

    expect(User::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(2);
});

it('allows creating users on the fleet plan without a limit', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    // Fleet has no user limit; create several beyond the starter cap...
    User::factory()->count(5)->forOrganization($organization)->create();

    expect(User::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(6);
});

it('blocks creating clients beyond the starter plan limit', function (): void {
    // Future trial keeps the org out of read-only enforcement so this test
    // exercises the plan-limit wall in isolation...
    $organization = Organization::factory()->create(['trial_ends_at' => now()->addDays(14)]);
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    // Use the model directly to fill the quota without hitting the limit
    // check itself...
    Client::factory()->count(25)->forOrganization($organization)->create();

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
                'plan' => 'starter',
                'count' => 25,
                'resource' => __('statuses.resource_clients'),
            ]),
        );

    expect(Client::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(25);
});

it('grants starter limits while on trial', function (): void {
    $organization = Organization::factory()->create(); // with trial...
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    // One user exists (the admin); the starter limit of 2 allows one more...
    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'role' => 'user',
        ]);

    expect(User::query()->withoutGlobalScopes()->where('organization_id', $organization->id)->count())->toBe(2);
});
