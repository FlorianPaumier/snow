<?php

declare(strict_types=1);

use App\Mail\LoginChallengeCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('renders login page', function (): void {
    $response = $this->fromRoute('home')
        ->get(route('login'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('session/create')
            ->has('canResetPassword')
            ->has('status'));
});

it('challenges every login with an emailed code', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

    Mail::assertQueued(LoginChallengeCodeMail::class, fn (LoginChallengeCodeMail $mail): bool => $mail->code !== '');

    $response->assertRedirectToRoute('login-challenge.show');

    expect(session('login.id'))->toBe($user->getKey())
        ->and($user->refresh()->login_challenge_code)->not->toBeNull()
        ->and($user->login_challenge_code_expires_at)->not->toBeNull();

    $this->assertGuest();
});

it('challenges login even when two-factor authentication is not enrolled', function (): void {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

    Mail::assertQueued(LoginChallengeCodeMail::class);

    $response->assertRedirectToRoute('login-challenge.show');

    $this->assertGuest();
});

it('remembers the remember-me preference for the challenge', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

    $response->assertRedirectToRoute('login-challenge.show');

    expect(session('login.remember'))->toBeTrue();

    $this->assertGuest();
});

it('fails with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

    $response->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('requires email', function (): void {
    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');
});

it('requires password', function (): void {
    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
        ]);

    $response->assertRedirectToRoute('login')
        ->assertSessionHasErrors('password');
});

it('may destroy a session', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('logout'));

    $response->assertRedirect('/');

    $this->assertGuest();
});

it('redirects authenticated users away from login', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('login'));

    $response->assertRedirectToRoute('dashboard');
});

it('throttles login attempts after too many failures', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    // Make 5 failed login attempts to trigger rate limiting
    for ($i = 0; $i < 5; $i++) {
        $this->fromRoute('login')
            ->post(route('login.store'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
    }

    // The 6th attempt should be throttled
    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

    $response->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');

    $errors = session('errors');
    expect($errors->get('email')[0])->toContain('Trop de tentatives de connexion');
});

it('does not clear the rate limit after successful login attempts', function (): void {
    Mail::fake();

    User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    // Each successful credential check consumes an attempt and earns one
    // challenge code email...
    for ($i = 0; $i < 5; $i++) {
        $this->fromRoute('login')
            ->post(route('login.store'), [
                'email' => 'test@example.com',
                'password' => 'password',
            ])
            ->assertRedirectToRoute('login-challenge.show');
    }

    Mail::assertQueued(LoginChallengeCodeMail::class, 5);

    // ...so the 6th attempt is throttled even with valid credentials, and no
    // 6th code is emailed: challenge codes cannot be mail-bombed.
    $response = $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('login')
        ->assertSessionHasErrors('email');

    $errors = session('errors');
    expect($errors->get('email')[0])->toContain('Trop de tentatives de connexion');

    Mail::assertQueued(LoginChallengeCodeMail::class, 5);

    $this->assertGuest();
});

it('completes the login flow by verifying the emailed challenge code', function (): void {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->fromRoute('login')
        ->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
        ->assertRedirectToRoute('login-challenge.show');

    /** @var LoginChallengeCodeMail $challengeMail */
    $challengeMail = Mail::queued(LoginChallengeCodeMail::class)->first();

    $this->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => $challengeMail->code,
        ])
        ->assertRedirectToRoute('dashboard');

    $this->assertAuthenticatedAs($user);
});

it('dispatches lockout event when rate limit is reached', function (): void {
    Event::fake([Lockout::class]);

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    // Make 6 failed login attempts to trigger rate limiting and Lockout event
    // The Lockout event fires on the 6th attempt when tooManyAttempts returns true
    for ($i = 0; $i < 6; $i++) {
        $this->fromRoute('login')
            ->post(route('login.store'), [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
    }

    Event::assertDispatched(Lockout::class);
});
