<?php

declare(strict_types=1);

use App\Mail\AccountValidationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('renders the account validation page', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('account-validation.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-account-validation/create')
            ->where('email', $user->email));
});

it('redirects verified users to dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('account-validation.show')
        ->get(route('account-validation.show'));

    $response->assertRedirectToRoute('dashboard');
});

it('may validate the account with a valid code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'validation_code' => Hash::make('123456'),
        'validation_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('account-validation.show')
        ->post(route('account-validation.store'), [
            'code' => '123456',
        ]);

    expect($user->refresh())
        ->hasVerifiedEmail()->toBeTrue()
        ->validation_code->toBeNull()
        ->validation_code_expires_at->toBeNull();

    $response->assertRedirect(route('dashboard', absolute: false).'?validated=1');
});

it('rejects an invalid code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'validation_code' => Hash::make('123456'),
        'validation_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('account-validation.show')
        ->post(route('account-validation.store'), [
            'code' => '654321',
        ]);

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();

    $response->assertSessionHasErrors('code');
});

it('rejects an expired code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'validation_code' => Hash::make('123456'),
        'validation_code_expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('account-validation.show')
        ->post(route('account-validation.store'), [
            'code' => '123456',
        ]);

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();

    $response->assertSessionHasErrors('code');
});

it('requires a 6 digit code', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('account-validation.show')
        ->post(route('account-validation.store'), [
            'code' => 'abc',
        ]);

    $response->assertSessionHasErrors('code');
});

it('may resend a validation code', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('account-validation.show')
        ->post(route('account-validation.resend'));

    Mail::assertQueued(AccountValidationCodeMail::class, fn (AccountValidationCodeMail $mail) => $mail->code !== '');

    expect($user->refresh()->validation_code)->not->toBeNull()
        ->and($user->validation_code_expires_at)->not->toBeNull();

    $response->assertRedirectToRoute('account-validation.show')
        ->assertSessionHas('status', 'validation-code-sent');
});
