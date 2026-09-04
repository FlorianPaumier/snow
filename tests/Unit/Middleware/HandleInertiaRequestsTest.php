<?php

declare(strict_types=1);

use App\Actions\GetSubscriptionSummary;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

it('shares app name from config', function (): void {
    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('name')
        ->and($shared['name'])->toBe(config('app.name'));
});

it('shares null user when guest', function (): void {
    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('auth')
        ->and($shared['auth'])->toHaveKey('user')
        ->and($shared['auth']['user'])->toBeNull();
});

it('shares authenticated user data', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['user'])->not->toBeNull()
        ->and($shared['auth']['user']->id)->toBe($user->id)
        ->and($shared['auth']['user']->name)->toBe('Test User')
        ->and($shared['auth']['user']->email)->toBe('test@example.com');
});

it('defaults sidebarOpen to true when no cookie', function (): void {
    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('sidebarOpen')
        ->and($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to true when cookie is true', function (): void {
    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'true');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to false when cookie is false', function (): void {
    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'false');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeFalse();
});

it('includes parent shared data', function (): void {
    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    // Parent Inertia middleware shares 'errors' by default
    expect($shared)->toHaveKey('errors');
});

it('shares the organization name and subscription for an organization member', function (): void {
    $user = User::factory()->create([
        'name' => 'Org Member',
        'email' => 'member@example.com',
    ]);

    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['organization'])->toBe([
        'id' => $user->organization_id,
        'name' => $user->organization->name,
        'ownerId' => $user->organization->owner_id,
    ])->and($shared['auth']['subscription'])->toBeArray();
});

it('shares the free plan summary for an expired-trial organization member', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $middleware = new HandleInertiaRequests(
        resolve(GetSubscriptionSummary::class),
    );

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['subscription'])->toBe([
        'plan' => 'free',
        'status' => 'free',
        'onTrial' => false,
        'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
    ])->and($shared['auth'])->not->toHaveKey('access');
});
