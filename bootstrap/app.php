<?php

declare(strict_types=1);

use App\Exceptions\OrganizationOwnerProtected;
use App\Exceptions\PlanLimitExceeded;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'locale']);

        // Stripe signs its webhook payloads; CSRF does not apply...
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);

        $middleware->web(append: [
            HandleAppearance::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Plan limits are business rules, not server errors: send the user
        // back with an upgrade prompt...
        $exceptions->render(function (PlanLimitExceeded $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['planLimit' => $exception->getMessage()]);
            }

            return back()->with('status', $exception->getMessage());
        });

        // The same courtesy applies to ownership protections...
        $exceptions->render(function (OrganizationOwnerProtected $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['ownerProtected' => $exception->getMessage()]);
            }

            return back()->with('status', $exception->getMessage());
        });
    })->create();
