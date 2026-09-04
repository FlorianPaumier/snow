<?php

declare(strict_types=1);

use App\Mail\LoginChallengeCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('renders the login challenge page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->get(route('login-challenge.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('login-challenge/create')
            ->where('email', $user->email));
});

it('redirects to login when there is no challenged user', function (): void {
    $response = $this->get(route('login-challenge.show'));

    $response->assertRedirectToRoute('login');
});

it('may confirm the login with a valid code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    expect($user->refresh())
        ->login_challenge_code->toBeNull()
        ->login_challenge_code_expires_at->toBeNull();

    $this->assertAuthenticatedAs($user);

    $response->assertRedirectToRoute('dashboard');
});

it('marks the email as verified when confirming the login challenge', function (): void {
    $user = User::factory()->withoutTwoFactor()->unverified()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '654321',
        ]);

    $this->assertGuest();

    $response->assertSessionHasErrors('code');
});

it('rejects an expired code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->subMinute(),
    ]);

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    $this->assertGuest();

    $response->assertSessionHasErrors('code');
});

it('rejects a code when there is none stored', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    $this->assertGuest();

    $response->assertSessionHasErrors('code');
});

it('requires a 6 digit code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => 'abc',
        ]);

    $response->assertSessionHasErrors('code');
});

it('may resend a login challenge code', function (): void {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.resend'));

    Mail::assertQueued(LoginChallengeCodeMail::class, fn (LoginChallengeCodeMail $mail): bool => $mail->code !== '');

    $oldCodeWasReplaced = ! Hash::check('123456', $user->refresh()->login_challenge_code);

    expect($oldCodeWasReplaced)->toBeTrue()
        ->and($user->login_challenge_code)->not->toBeNull()
        ->and($user->login_challenge_code_expires_at)->not->toBeNull();

    $response->assertRedirectToRoute('login-challenge.show')
        ->assertSessionHas('status', 'login-challenge-code-sent');
});

it('redirects resend to login when there is no challenged user', function (): void {
    Mail::fake();

    $response = $this->fromRoute('login-challenge.show')
        ->post(route('login-challenge.resend'));

    $response->assertRedirectToRoute('login');
});

it('redirects to login when the challenge session points at an unknown user', function (): void {
    $this->withSession([
        'login.id' => '00000000-0000-0000-0000-000000000000',
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ])->assertRedirectToRoute('login');
});

it('throttles challenge attempts after too many failures', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->withSession([
            'login.id' => $user->getKey(),
            'login.remember' => false,
        ])->fromRoute('login-challenge.show')
            ->post(route('login-challenge.store'), [
                'code' => '654321',
            ]);
    }

    // The 6th attempt is throttled even when it carries the correct code...
    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    $this->assertGuest();

    $response->assertRedirectToRoute('login-challenge.show')
        ->assertSessionHasErrors('code');

    $errors = session('errors');
    expect($errors->get('code')[0])->toContain('Trop de tentatives de connexion')
        // ...and the stored code was not consumed by the throttled attempt.
        ->and($user->refresh()->login_challenge_code)->not->toBeNull();
});

it('allows a correct code within the attempt limit', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    for ($i = 0; $i < 4; $i++) {
        $this->withSession([
            'login.id' => $user->getKey(),
            'login.remember' => false,
        ])->fromRoute('login-challenge.show')
            ->post(route('login-challenge.store'), [
                'code' => '654321',
            ]);
    }

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    $this->assertAuthenticatedAs($user);

    $response->assertRedirectToRoute('dashboard');
});

it('clears the challenge attempt counter once the code is verified', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    // Four failed attempts leave the counter one shy of the limit...
    for ($i = 0; $i < 4; $i++) {
        $this->withSession([
            'login.id' => $user->getKey(),
            'login.remember' => false,
        ])->fromRoute('login-challenge.show')
            ->post(route('login-challenge.store'), [
                'code' => '654321',
            ]);
    }

    // ...so the correct code still verifies the login.
    $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    $this->assertAuthenticatedAs($user);

    // A later challenge flow starts from a cleared counter: without the
    // clear, the failure below would exhaust the limit and the correct
    // code afterwards would be throttled instead of logging in.
    $this->post(route('logout'));

    // Refresh so the in-memory model sees the nulled code columns before
    // re-arming them (frozen time makes the new expiry identical to the
    // stale in-memory original, which would skip persisting it).
    $user->refresh();

    $user->forceFill([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ])->save();

    $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '654321',
        ]);

    $response = $this->withSession([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ])->fromRoute('login-challenge.show')
        ->post(route('login-challenge.store'), [
            'code' => '123456',
        ]);

    $this->assertAuthenticatedAs($user);

    $response->assertRedirectToRoute('dashboard');
});

it('dispatches a lockout event when the challenge is rate limited', function (): void {
    Event::fake([Lockout::class]);

    $user = User::factory()->withoutTwoFactor()->create([
        'login_challenge_code' => Hash::make('123456'),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ]);

    for ($i = 0; $i < 6; $i++) {
        $this->withSession([
            'login.id' => $user->getKey(),
            'login.remember' => false,
        ])->fromRoute('login-challenge.show')
            ->post(route('login-challenge.store'), [
                'code' => '654321',
            ]);
    }

    Event::assertDispatched(Lockout::class);
});
