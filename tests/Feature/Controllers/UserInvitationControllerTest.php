<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

function invitationFor(User $user): string
{
    return (string) Password::broker('invitations')->createToken($user);
}

it('shows the invitation page for a pending member', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->pendingInvitation()->forOrganization($organization)->create();

    $this
        ->get(route('user-invitation.create', ['token' => 'any-token']).'?email='.$user->email)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-invitation/create')
            ->where('email', $user->email)
            ->where('organizationName', $organization->name)
            ->where('token', 'any-token'));
});

it('reveals no organization name for a member who already accepted', function (): void {
    // The accept page is unauthenticated: answering for an already-active
    // member would let anyone enumerate emails and organizations...
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $this
        ->get(route('user-invitation.create', ['token' => 'any-token']).'?email='.$user->email)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-invitation/create')
            ->where('email', $user->email)
            ->where('organizationName', null));
});

it('accepts an invitation and lets the member choose their password', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->pendingInvitation()->forOrganization($organization)->create();

    $this->post(route('user-invitation.store'), [
        'token' => invitationFor($user),
        'email' => $user->email,
        'password' => 'my-own-secret-password',
        'password_confirmation' => 'my-own-secret-password',
    ])->assertRedirect(route('login'));

    $user->refresh();

    expect(Hash::check('my-own-secret-password', $user->password))->toBeTrue()
        ->and($user->hasPendingInvitation())->toBeFalse()
        // The invitation link proves control of the inbox, so accepting
        // also verifies the email...
        ->and($user->email_verified_at)->not->toBeNull();
});

it('rejects an invalid invitation token', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->pendingInvitation()->forOrganization($organization)->create();

    invitationFor($user);

    $this->post(route('user-invitation.store'), [
        'token' => 'wrong-token',
        'email' => $user->email,
        'password' => 'my-own-secret-password',
        'password_confirmation' => 'my-own-secret-password',
    ])->assertSessionHasErrors('email');

    expect($user->fresh()->hasPendingInvitation())->toBeTrue();
});

it('rejects an expired invitation', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->pendingInvitation()->forOrganization($organization)->create();

    $token = invitationFor($user);

    // Invitations live for 72 hours...
    $this->travel(8)->days();

    $this->post(route('user-invitation.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'my-own-secret-password',
        'password_confirmation' => 'my-own-secret-password',
    ])->assertSessionHasErrors('email');

    expect($user->fresh()->hasPendingInvitation())->toBeTrue();
});

it('requires an email and password to accept an invitation', function (): void {
    $this->post(route('user-invitation.store'), [])
        ->assertSessionHasErrors(['email', 'token', 'password']);
});
