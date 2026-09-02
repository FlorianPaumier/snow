<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;

it('renders the organization settings for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('organization.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('organization/edit')
            ->where('organization.name', $admin->organization->name)
            ->where('organization.ownerId', $admin->organization->owner_id)
            ->has('members'));
});

it('updates the organization name', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('organization.update'), ['name' => 'Renamed Snow Co'])
        ->assertRedirect();

    expect($admin->organization->refresh()->name)->toBe('Renamed Snow Co');
});

it('validates the organization name', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('organization.update'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('forbids staff from organization settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

it('requires guests to authenticate for organization settings', function (): void {
    $this->get(route('organization.edit'))
        ->assertRedirect(route('login'));
});

it('lets the owner transfer ownership to a member', function (): void {
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->forOrganization($organization)->create();
    $organization->update(['owner_id' => $owner->id]);

    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->put(route('organization.transfer'), ['user_id' => $member->id])
        ->assertRedirect(route('organization.edit'));

    expect($organization->refresh()->owner_id)->toBe($member->id)
        // The new owner is promoted; the previous owner stays an admin...
        ->and($member->refresh()->role)->toBe(UserRole::ROLE_ADMIN)
        ->and($owner->refresh()->role)->toBe(UserRole::ROLE_ADMIN);
});

it('forbids non-owners from transferring ownership', function (): void {
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->forOrganization($organization)->create();
    $organization->update(['owner_id' => $owner->id]);

    $otherAdmin = User::factory()->admin()->forOrganization($organization)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($otherAdmin)
        ->put(route('organization.transfer'), ['user_id' => $member->id])
        ->assertForbidden();

    expect($organization->refresh()->owner_id)->toBe($owner->id);
});

it('rejects transferring ownership to a member of another organization', function (): void {
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->forOrganization($organization)->create();
    $organization->update(['owner_id' => $owner->id]);

    $outsider = User::factory()->create();

    $this->actingAs($owner)
        ->put(route('organization.transfer'), ['user_id' => $outsider->id])
        ->assertSessionHasErrors('user_id');

    expect($organization->refresh()->owner_id)->toBe($owner->id);
});
