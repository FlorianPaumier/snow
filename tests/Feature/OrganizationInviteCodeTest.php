<?php

declare(strict_types=1);

use App\Mail\LoginChallengeCodeMail;
use App\Mail\OrganizationInviteCodeMail;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('emails a single-use invite code to the prospect', function (): void {
    Mail::fake();

    $this->artisan('organization:send-invite-code', ['email' => 'prospect@example.com'])
        ->expectsOutputToContain('Invite code sent to prospect@example.com.')
        ->assertSuccessful();

    // The mailable is queued (ShouldQueue)...
    Mail::assertQueued(OrganizationInviteCodeMail::class, 1);

    Mail::assertQueued(OrganizationInviteCodeMail::class, function (OrganizationInviteCodeMail $mail): bool {
        return $mail->hasTo('prospect@example.com')
            && $mail->code !== ''
            && mb_strlen($mail->code) === 10;
    });

    // Only the hash is stored, never the plaintext code...
    $invite = OrganizationInvite::query()->sole();

    $sentCode = Mail::queued(OrganizationInviteCodeMail::class)[0]->code;

    expect($invite->email)->toBe('prospect@example.com')
        ->and($invite->claimed_at)->toBeNull()
        ->and($invite->expires_at)->toBeGreaterThan(now())
        ->and($invite->code)->not->toBe($sentCode)
        ->and(Hash::check($sentCode, $invite->code))->toBeTrue();
});

it('stores the invite code hashed and lowercase-binds the email', function (): void {
    Mail::fake();

    $this->artisan('organization:send-invite-code', ['email' => 'Prospect@Example.COM'])
        ->assertSuccessful();

    $invite = OrganizationInvite::query()->sole();

    $sentCode = Mail::queued(OrganizationInviteCodeMail::class)[0]->code;

    expect($invite->email)->toBe('prospect@example.com')
        // A hash never contains the 10-char plaintext: the stored value is
        // provably not the raw code...
        ->and($invite->code)->not->toBe($sentCode)
        ->and(Hash::check($sentCode, $invite->code))->toBeTrue();
});

it('registers a user with a valid invite code and burns the code', function (): void {
    Event::fake([Registered::class]);
    Mail::fake();

    $code = issueInvite();

    $response = $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(inviteCode: $code));

    $response->assertRedirectToRoute('login-challenge.show');

    Mail::assertQueued(LoginChallengeCodeMail::class);

    $user = User::query()->withoutGlobalScopes()->where('email', 'prospect@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->organization->name)->toBe('Test Company');

    $invite = OrganizationInvite::query()->sole();

    expect($invite->isClaimed())->toBeTrue();

    // The burned code cannot found a second organization...
    $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(
            email: 'other@example.com',
            inviteCode: $code,
        ))
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors('invite_code');
});

it('requires an invite code to register', function (): void {
    Mail::fake();

    $this->fromRoute('register')
        ->post(route('register.store'), [
            ...registrationPayload(),
            'invite_code' => null,
        ])
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors('invite_code');

    expect(User::query()->withoutGlobalScopes()->where('email', 'prospect@example.com')->exists())->toBeFalse();
});

it('rejects registration with an unknown invite code', function (): void {
    Mail::fake();

    $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(inviteCode: 'GARBAGE1234'))
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors('invite_code');

    expect(User::query()->withoutGlobalScopes()->where('email', 'prospect@example.com')->exists())->toBeFalse();
});

it('rejects a code issued for a different email', function (): void {
    Mail::fake();

    $foreignCode = issueInvite('someone-else@example.com');

    $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(inviteCode: $foreignCode))
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors('invite_code');

    expect(User::query()->withoutGlobalScopes()->where('email', 'prospect@example.com')->exists())->toBeFalse();
});

it('rejects an expired invite code', function (): void {
    Mail::fake();
    Event::fake([Registered::class]);

    $code = 'EXPIREDCOD';

    OrganizationInvite::query()->create([
        'email' => 'prospect@example.com',
        'code' => Hash::make($code),
        'expires_at' => now()->subDay(),
    ]);

    $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(inviteCode: $code))
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors('invite_code');

    expect(User::query()->withoutGlobalScopes()->where('email', 'prospect@example.com')->exists())->toBeFalse();
});

it('prefers the newest unclaimed invite for the email', function (): void {
    Mail::fake();
    Event::fake([Registered::class]);

    $old = 'OLDCODE123';
    $newest = 'NEWESTCODE';

    OrganizationInvite::query()->create([
        'email' => 'prospect@example.com',
        'code' => Hash::make($old),
        'expires_at' => now()->addDays(7),
    ])->forceFill(['created_at' => now()->subHour()])->save();

    OrganizationInvite::query()->create([
        'email' => 'prospect@example.com',
        'code' => Hash::make($newest),
        'expires_at' => now()->addDays(7),
    ]);

    // The old code is superseded by the newest resend...
    $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(inviteCode: $old))
        ->assertRedirectToRoute('register')
        ->assertSessionHasErrors('invite_code');

    // ...while the newest one registers.
    $this->fromRoute('register')
        ->post(route('register.store'), registrationPayload(inviteCode: $newest))
        ->assertRedirectToRoute('login-challenge.show');

    expect(User::query()->withoutGlobalScopes()->where('email', 'prospect@example.com')->exists())->toBeTrue();
});
