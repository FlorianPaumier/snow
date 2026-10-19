<?php

declare(strict_types=1);

use App\Exceptions\OrganizationOwnerProtected;
use App\Exceptions\PlanFeatureLocked;
use App\Exceptions\PlanLimitExceeded;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'locale']);

        // Stripe signs its webhook payloads; CSRF does not apply...
        $middleware->validateCsrfTokens(except: ['stripe/webhook', 'stripe/connect/webhook']);

        $middleware->web(append: [
            HandleAppearance::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $code = $response->getStatusCode();

            if ($request->expectsJson() || $code < 400 || $code >= 600) {
                return $response;
            }

            $locale = $request->cookie('locale', config('app.locale'));
            app()->setLocale(in_array($locale, ['fr', 'en'], true) ? $locale : 'fr');

            // Error pages must not depend on tenant data or expose shared user props.
            Inertia::flushShared();
            Inertia::setRootView('app');

            try {
                $error = Inertia::render('error', [
                    'code' => $code,
                    'title' => __('errors.titles')[$code] ?? __('errors.generic'),
                    'homeLabel' => __('errors.home'),
                    'errors' => (object) [],
                ])->toResponse($request)->setStatusCode($code);
            } catch (Throwable $renderException) {
                report($renderException);
                $error = response()->view('errors.minimal', ['status' => $code], $code);
            }

            $headers = $response->headers->all();
            unset($headers['content-type'], $headers['content-length']);
            $error->headers->add($headers);
            $error->headers->set('Cache-Control', 'no-store');
            $error->headers->set('X-Robots-Tag', 'noindex, nofollow');

            return $error;
        });

        // Plan limits are business rules, not server errors: send the user
        // back with an upgrade prompt...
        $exceptions->render(function (PlanLimitExceeded $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['planLimit' => $exception->getMessage()]);
            }

            return back()->with('status', $exception->getMessage());
        });

        // A feature behind the paid tier gets the same upgrade prompt...
        $exceptions->render(function (PlanFeatureLocked $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['featureLocked' => $exception->getMessage()]);
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
