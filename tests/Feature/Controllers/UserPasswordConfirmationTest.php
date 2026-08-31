<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('renders the confirm password page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('user-password-confirmation/create'));
});

it('requires authentication', function (): void {
    $this->fromRoute('dashboard')
        ->get(route('password.confirm'))
        ->assertRedirectToRoute('login');
});

it('requires a password to confirm', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->fromRoute('password.confirm')
        ->post(route('password.confirm.store'), [])
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHasErrors('password');
});

it('rejects a wrong password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->actingAs($user)
        ->fromRoute('password.confirm')
        ->post(route('password.confirm.store'), [
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHasErrors('password');

    expect(session()->has('auth.password_confirmed_at'))->toBeFalse();
});

it('confirms the password and redirects to the intended route', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->actingAs($user)
        ->fromRoute('password.confirm')
        // The intended destination is remembered from the confirm redirect...
        ->post(route('password.confirm.store'), [
            'password' => 'correct-password',
        ])
        ->assertRedirect(route('dashboard'));

    expect(session()->has('auth.password_confirmed_at'))->toBeTrue();
});

it('keeps the confirmation within the timeout window', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => true]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->subHours(4)->unix()])
        ->get(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => false]);
});
