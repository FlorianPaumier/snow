<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureOrganizationNotReadOnly;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

function read_only_middleware_user(Organization $organization): User
{
    return User::factory()->admin()->forOrganization($organization)->create();
}

it('passes read requests through untouched', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = read_only_middleware_user($organization);

    $request = Request::create('/dashboard', 'GET');
    $request->setUserResolver(fn () => $user);

    $passed = false;

    $middleware = app(EnsureOrganizationNotReadOnly::class);
    $response = $middleware->handle($request, function () use (&$passed): Response {
        $passed = true;

        return new Response('next');
    });

    expect($passed)->toBeTrue()
        ->and($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('next');
});

it('blocks mutating requests for a read-only organization', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = read_only_middleware_user($organization);

    $request = Request::create('/clients', 'POST');
    $request->setUserResolver(fn () => $user);

    // The middleware reads the route name from the request's route
    // resolver; bind a real route so getName() returns 'clients.store'.
    // (Route::getRoutes() returns a RouteCollection, which has no
    // addRoute() — that method is on the Router — so the route instance
    // is built directly and bound via the resolver instead...)
    $route = new Route(['POST'], '/clients', ['as' => 'clients.store']);
    $request->setRouteResolver(fn () => $route);

    app(EnsureOrganizationNotReadOnly::class)->handle($request, fn (): Response => new Response('next'));
})->throws(App\Exceptions\OrganizationReadOnly::class);

it('exempts billing routes from the block', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = read_only_middleware_user($organization);

    $request = Request::create('/settings/billing', 'POST');
    $request->setUserResolver(fn () => $user);

    $route = new Route(['POST'], '/settings/billing', ['as' => 'billing.checkout']);
    $request->setRouteResolver(fn () => $route);

    $passed = false;

    $middleware = app(EnsureOrganizationNotReadOnly::class);
    $response = $middleware->handle($request, function () use (&$passed): Response {
        $passed = true;

        return new Response('next');
    });

    expect($passed)->toBeTrue()
        ->and($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('next');
});

it('passes mutating requests through when no user is authenticated', function (): void {
    // A guest (or organization-less user) has no organization to gate:
    // the middleware must not block the request...
    $request = Request::create('/clients', 'POST');
    $request->setUserResolver(fn () => null);

    $route = new Route(['POST'], '/clients', ['as' => 'clients.store']);
    $request->setRouteResolver(fn () => $route);

    $passed = false;

    $middleware = app(EnsureOrganizationNotReadOnly::class);
    $response = $middleware->handle($request, function () use (&$passed): Response {
        $passed = true;

        return new Response('next');
    });

    expect($passed)->toBeTrue()
        ->and($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('next');
});

it('passes mutating requests through for an organization with valid access', function (): void {
    $organization = Organization::factory()->create();
    $user = read_only_middleware_user($organization);

    $request = Request::create('/clients', 'POST');
    $request->setUserResolver(fn () => $user);

    $route = new Route(['POST'], '/clients', ['as' => 'clients.store']);
    $request->setRouteResolver(fn () => $route);

    $passed = false;

    $middleware = app(EnsureOrganizationNotReadOnly::class);
    $response = $middleware->handle($request, function () use (&$passed): Response {
        $passed = true;

        return new Response('next');
    });

    expect($passed)->toBeTrue()
        ->and($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('next');
});
