<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Event;
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
    $admin = User::factory()->admin()->create();
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

it('creates a user', function (): void {
    Notification::fake();
    Event::fake();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Jean Tremblay',
            'email' => 'jean@example.com',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
            'role' => UserRole::ROLE_WORKER->value,
        ])
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'jean@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Jean Tremblay')
        ->and($user->role)->toBe(UserRole::ROLE_WORKER);
});

it('validates the create user payload', function (array $payload): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), $payload)
        ->assertSessionHasErrors();
})->with([
    'missing name' => [['name' => null, 'email' => 'a@example.com', 'password' => 'super-secret-password', 'password_confirmation' => 'super-secret-password', 'role' => 'user']],
    'invalid email' => [['name' => 'Jean', 'email' => 'not-an-email', 'password' => 'super-secret-password', 'password_confirmation' => 'super-secret-password', 'role' => 'user']],
    'unconfirmed password' => [['name' => 'Jean', 'email' => 'a@example.com', 'password' => 'super-secret-password', 'password_confirmation' => 'wrong', 'role' => 'user']],
    'invalid role' => [['name' => 'Jean', 'email' => 'a@example.com', 'password' => 'super-secret-password', 'password_confirmation' => 'super-secret-password', 'role' => 'owner']],
]);

it('rejects duplicate emails', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Copy',
            'email' => $admin->email,
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
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

it('updates a user without changing their password', function (): void {
    Notification::fake();
    Event::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();
    $originalHash = $user->password;

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::ROLE_ADMIN->value,
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->role)->toBe(UserRole::ROLE_ADMIN)
        ->and($user->password)->toBe($originalHash);
});

it('updates a user password when provided', function (): void {
    Notification::fake();
    Event::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();
    $originalHash = $user->password;

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
            'role' => $user->role->value,
        ])
        ->assertRedirect(route('users.index'));

    expect($user->refresh()->password)->not->toBe($originalHash);
});

it('deletes a user', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->forOrganization($admin->organization)->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect($user->fresh())->toBeNull();
});
