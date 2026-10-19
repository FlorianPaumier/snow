<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Mail\UserInvitationMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

it('renders the users index for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-management/index')
            ->has('users.data')
            ->where('filters.sort', 'created_at')
            ->where('filters.direction', 'desc'));
});

it('searches users by name or email', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->forOrganization($admin->organization)->create(['name' => 'Jean Tremblay', 'email' => 'jean@example.com']);
    User::factory()->forOrganization($admin->organization)->create(['name' => 'Marie Roy', 'email' => 'marie@example.com']);

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'jean']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'jean@example.com'));

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'marie@example.com']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Marie Roy'));
});

it('sorts users by the requested column and direction', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Marie']);
    User::factory()->forOrganization($admin->organization)->create(['name' => 'Zack', 'email' => 'zack@example.com']);
    User::factory()->forOrganization($admin->organization)->create(['name' => 'Anna', 'email' => 'anna@example.com']);

    $this->actingAs($admin)
        ->get(route('users.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.data.0.name', 'Anna')
            ->where('users.data.2.name', 'Zack'));

    $this->actingAs($admin)
        ->get(route('users.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.data.0.name', 'Zack'));
});

it('rejects invalid sort parameters', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.index', ['sort' => 'password', 'direction' => 'sideways']))
        ->assertSessionHasErrors(['sort', 'direction']);
});

it('paginates users', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->count(20)->forOrganization($admin->organization)->create();

    $response = $this->actingAs($admin)
        ->get(route('users.index', ['page' => 2]))
        ->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->where('users.current_page', 2)
        ->where('users.per_page', 15)
        ->where('users.total', 21)
        ->has('users.data', 6));
});

it('prevents non-admins from managing users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

it('creates a user and emails their invitation', function (): void {
    Mail::fake();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Jean Tremblay',
            'email' => 'jean@example.com',
            'role' => UserRole::ROLE_WORKER->value,
        ])
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'jean@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Jean Tremblay')
        ->and($user->role)->toBe(UserRole::ROLE_WORKER)
        ->and($user->hasPendingInvitation())->toBeTrue();

    Mail::assertQueued(UserInvitationMail::class);
});

it('validates the create user payload', function (array $payload): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), $payload)
        ->assertSessionHasErrors();
})->with([
    'missing name' => [['name' => null, 'email' => 'a@example.com', 'role' => 'user']],
    'invalid email' => [['name' => 'Jean', 'email' => 'not-an-email', 'role' => 'user']],
    'invalid role' => [['name' => 'Jean', 'email' => 'a@example.com', 'role' => 'owner']],
]);

it('rejects duplicate emails', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Copy',
            'email' => $admin->email,
            'role' => UserRole::ROLE_USER->value,
        ])
        ->assertSessionHasErrors('email');
});

it('renders the edit page', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-management/edit')
            ->where('user.id', $user->id));
});

it('updates a user', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();
    $originalHash = $user->password;

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => UserRole::ROLE_ADMIN->value,
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->role)->toBe(UserRole::ROLE_ADMIN)
        ->and($user->password)->toBe($originalHash);
});

it('emails a password reset link to an active user', function (): void {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->post(route('users.send-password-reset', $user))
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resends the invitation to a pending user', function (): void {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->pendingInvitation()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->post(route('users.send-password-reset', $user))
        ->assertRedirect();

    Mail::assertQueued(
        UserInvitationMail::class,
        fn (UserInvitationMail $mail): bool => $mail->email === $user->email,
    );
});

it('refuses to demote the organization owner', function (): void {
    $owner = User::factory()->admin()->create();
    $owner->organization->update(['owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->put(route('users.update', $owner), [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => UserRole::ROLE_USER->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('status', __('statuses.organization_owner_role'));

    expect($owner->fresh()->role)->toBe(UserRole::ROLE_ADMIN);
});

it('carries the owner protection to the user edit page error bag for inertia requests', function (): void {
    $owner = User::factory()->admin()->create();
    $owner->organization->update(['owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->fromRoute('users.edit', $owner)
        ->withHeaders(['X-Inertia' => 'true'])
        ->put(route('users.update', $owner), [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => UserRole::ROLE_USER->value,
        ])
        ->assertRedirectToRoute('users.edit', $owner)
        ->assertSessionHasErrors(['ownerProtected' => __('statuses.organization_owner_role', [], 'fr')]);

    expect($owner->fresh()->role)->toBe(UserRole::ROLE_ADMIN);
});

it('deletes a user', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect($user->fresh())->toBeNull();
});

it('refuses to delete the organization owner', function (): void {
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->forOrganization($organization)->create();
    $organization->update(['owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->delete(route('users.destroy', $owner))
        ->assertRedirect()
        ->assertSessionHas('status', __('statuses.organization_owner_delete'));

    expect($owner->fresh())->not->toBeNull();
});

it('refuses to let another admin delete the organization owner', function (): void {
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->forOrganization($organization)->create();
    $organization->update(['owner_id' => $owner->id]);
    $otherAdmin = User::factory()->admin()->forOrganization($organization)->create();

    $this->actingAs($otherAdmin)
        ->delete(route('users.destroy', $owner))
        ->assertRedirect()
        ->assertSessionHas('status', __('statuses.organization_owner_delete'));

    expect($owner->fresh())->not->toBeNull();
});

it('renders the user creation page for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('user-management/create'));
});
