<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

// Creating a user stamps the acting admin's organization; the action
// requires an authenticated context...
beforeEach(function (): void {
    $this->user = User::factory()->admin()->create();

    Auth::login($this->user);
});

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = resolve(CreateUser::class);

    $user = $action->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
    ], 'password');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('example@email.com')
        ->and($user->password)->not->toBe('password');

    Event::assertDispatched(Registered::class);
});

it('creates a user without plan enforcement when unauthenticated', function (): void {
    Auth::logout();

    Event::fake([Registered::class]);

    $organization = Organization::factory()->create();

    // Console contexts pass the organization explicitly: no acting user
    // means no plan limit is enforced...
    $user = resolve(CreateUser::class)->handle([
        'name' => 'Console User',
        'email' => 'console@email.com',
        'organization_id' => $organization->id,
    ], 'password');

    expect($user->organization_id)->toBe($organization->id);
});

it('skips the limit check on an unlimited plan', function (): void {
    Event::fake([Registered::class]);

    // Any subscribed plan with unlimited users lifts the ceiling...
    $this->user->organization->subscriptions()->create([
        'type' => 'fleet',
        'stripe_id' => 'sub_create_user_unlimited',
        'stripe_status' => 'active',
        'stripe_price' => 'price_fleet',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $users = collect(range(1, 3))->map(fn (): User => User::factory()->forOrganization($this->user->organization)->create());

    expect($users)->toHaveCount(3);

    // The fleet plan allows unlimited users; creation succeeds regardless
    // of the current count...
    $fourth = resolve(CreateUser::class)->handle([
        'name' => 'Fourth User',
        'email' => 'fourth@email.com',
    ], 'password');

    expect($fourth->organization_id)->toBe($this->user->organization_id);
});
