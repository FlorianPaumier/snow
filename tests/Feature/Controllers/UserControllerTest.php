<?php

declare(strict_types=1);

use App\Mail\LoginChallengeCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('renders registration page', function (): void {
    $response = $this->fromRoute('home')
        ->get(route('register'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('user/create'));
});

it('may register a new user', function (): void {
    Event::fake([Registered::class]);
    Mail::fake();

    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'terms' => true,
        ]);

    $response->assertRedirectToRoute('login-challenge.show');

    Mail::assertQueued(LoginChallengeCodeMail::class);

    // The user is queried unscoped: no authenticated user exists during
    // registration, and the organization scope would hide the row...
    $user = User::query()->withoutGlobalScopes()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->organization->name)->toBe('Test Company')
        ->and($user->organization->trial_ends_at)->not->toBeNull()
        ->and(Hash::check('password1234', $user->password))->toBeTrue()
        ->and($user->login_challenge_code)->not->toBeNull()
        ->and(session('login.id'))->toBe($user->getKey());

    $this->assertGuest();

    Event::assertDispatched(Registered::class);
});

it('requires name', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('name');
});

it('requires email', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'organization' => 'Test Company',
            'invite_code' => 'ANYSOURCE1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('email');
});

it('requires valid email', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'organization' => 'Test Company',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('email');
});

it('requires unique email', function (): void {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('email');
});

it('requires an organization', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('organization');
});

it('requires password', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('password');
});

it('requires password confirmation', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('password');
});

it('requires matching password confirmation', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('password');
});

it('requires accepting the terms to register', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('terms');

    expect(User::query()->withoutGlobalScopes()->where('email', 'test@example.com')->exists())->toBeFalse();
});

it('rejects a terms value that is not accepted', function (): void {
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization' => 'Test Company',
            'invite_code' => issueInvite('test@example.com'),
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'terms' => 'no',
        ]);

    $response->assertRedirectToRoute('register')
        ->assertSessionHasErrors('terms');

    expect(User::query()->withoutGlobalScopes()->where('email', 'test@example.com')->exists())->toBeFalse();
});

it('sets the registering user as the organization owner', function (): void {
    Event::fake([Registered::class]);
    Mail::fake();

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'owner@example.com',
        'organization' => 'Test Company',
        'invite_code' => issueInvite('owner@example.com'),
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'terms' => true,
    ]);

    $user = User::query()->withoutGlobalScopes()->where('email', 'owner@example.com')->first();

    expect($user->organization->owner_id)->toBe($user->id)
        ->and($user->isOwner())->toBeTrue();
});

it('throttles registration attempts', function (): void {
    Event::fake([Registered::class]);
    Mail::fake();

    $payload = fn (string $email): array => [
        'name' => 'Test User',
        'organization' => 'Test Company',
        'invite_code' => issueInvite($email),
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'terms' => true,
    ];

    // The first five registrations within the same minute go through...
    for ($i = 0; $i < 5; $i++) {
        $email = sprintf('test%d@example.com', $i);

        $this->fromRoute('register')
            ->post(route('register.store'), [
                ...$payload($email),
                'email' => $email,
            ])
            ->assertRedirectToRoute('login-challenge.show');
    }

    // ...and the sixth is rejected with 429 before any organization or
    // trial is created: bots cannot farm accounts and 14-day trials.
    $response = $this->fromRoute('register')
        ->post(route('register.store'), [
            ...$payload('bot@example.com'),
            'email' => 'bot@example.com',
        ]);

    $response->assertStatus(429);

    expect(User::query()->withoutGlobalScopes()->where('email', 'bot@example.com')->exists())->toBeFalse();

    Event::assertDispatchedTimes(Registered::class, 5);
});

it('may delete user account', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('home');

    expect($user->fresh())->toBeNull();

    $this->assertGuest();
});

it('requires password to delete account', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), []);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

it('requires correct password to delete account', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

it('refuses to delete the account of the organization owner', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $user->organization->update(['owner_id' => $user->id]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'password',
        ]);

    // The refusal happens before the session is torn down, so the owner
    // stays signed in and lands back on the profile page...
    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHas('status', __('statuses.organization_owner_delete'));

    $this->assertAuthenticatedAs($user);

    expect($user->fresh())->not->toBeNull();
});

it('carries the owner protection to the profile page error bag for inertia requests', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $user->organization->update(['owner_id' => $user->id]);

    $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->withHeaders(['X-Inertia' => 'true'])
        ->delete(route('user.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors(['ownerProtected' => __('statuses.organization_owner_delete', [], 'fr')]);

    // Still authenticated: the redirect survives the auth middleware and
    // the profile page re-renders with the alert message in the bag. The
    // Inertia header is dropped and the session cookie forwarded so the
    // GET, like a browser following the redirect, reads the flashed bag...
    $this->assertAuthenticatedAs($user);

    $this->withoutHeader('X-Inertia')
        ->withCookie(session()->getName(), session()->getId())
        ->get(route('user-profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-profile/edit')
            ->where('errors.ownerProtected', __('statuses.organization_owner_delete', [], 'fr')));

    expect($user->fresh())->not->toBeNull();
});

it('redirects authenticated users away from registration', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('register'));

    $response->assertRedirectToRoute('dashboard');
});
