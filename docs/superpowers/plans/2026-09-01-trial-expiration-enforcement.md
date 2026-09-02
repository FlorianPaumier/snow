# Trial Expiration Enforcement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce read-only access for organizations whose trial has ended without a valid subscription, with a one-time 24h admin unlock and a persistent upgrade banner.

**Architecture:** A `GetSubscriptionAccess` action computes an access verdict from the trial date, Cashier `valid()` subscriptions, and the unlock column, cached in `TenantCache` with a TTL capped at the next known time transition. An `EnsureOrganizationNotReadOnly` middleware on the main app route group blocks mutating verbs for read-only orgs (billing routes exempt) by throwing a rendered `OrganizationReadOnly` exception. The verdict is shared to every Inertia request as `auth.access` for a persistent banner with an upgrade CTA and (for admins) the unlock button. No scheduled jobs — time transitions are handled by TTL-capped cache entries, event transitions by the existing webhook/action cache flushes.

**Tech Stack:** Laravel 13, Laravel Cashier v16.7 (Billable on `Organization`), Inertia v3 + React, Wayfinder, Tailwind/shadcn, Redis (`array` store in tests), Pest v5.

**Spec:** `docs/superpowers/specs/2026-09-01-trial-expiration-enforcement-design.md`

## Global Constraints

- PHP actions live in `app/Actions`, named for what they do, no suffix, single `handle()` method, dependencies via constructor promotion. Create with `php artisan make:action "{name}" --no-interaction`.
- `TenantCache::remember(string $name, array $params, Closure $callback, ?int $ttl = null)` already accepts a computed TTL — no cache changes are needed.
- "Valid subscription" = any subscription row where Cashier `Subscription::valid()` returns true (`active() || onTrial() || onGracePeriod()`; per this app's defaults, past-due/unpaid/incomplete-expired are NOT valid). Evaluate over `$organization->subscriptions` — do NOT use `$organization->subscription('default')` (plan-keyed types never match).
- Verdict cache entry name: `'subscription-access'`; TTL = `min(900, seconds until next time transition)`; floor of 1 second.
- Unlock is one-time per organization lifetime: `access_unlock_until` must be null to grant.
- Exempt mutating requests only for routes whose name starts with `billing.`. Exempt verbs: GET, HEAD, OPTIONS.
- All copy added in both `lang/en/*` and `lang/fr/*` (server) and both `resources/js/lang/en.ts` and `fr.ts` (frontend). Frontend interpolation uses `interpolate(str, {key: value})` from `@/lib/utils` with `:key` placeholders.
- Every PHP file `declare(strict_types=1);`, `final readonly class` where siblings are, no empty zero-arg constructors, PHPDoc array shapes, no superfluous annotations.
- Run `vendor/bin/pint --dirty --format agent` after each task that touches PHP. Run `npm run test:types` after tasks that touch TS. Run tests with `php artisan test --compact --filter=...` (frozen time is global in `tests/Pest.php`; fake Stripe via direct `subscriptions()->create([...])` rows, never the API).
- Commit after every task. Test commands below assume CWD `/Users/florianpaumier/Documents/dev/snow/laravel-app`.

---

### Task 1: `expired()` and `unlocked()` factory states

**Files:**
- Modify: `database/factories/OrganizationFactory.php:31-36`
- Test: none new (states are exercised by Tasks 2+; this task's verification is the full suite staying green)

**Interfaces:**
- Produces: `OrganizationFactory::expired()` — sets `trial_ends_at` to yesterday; `OrganizationFactory::unlocked()` — sets `access_unlock_until` to 24h from now. Later tasks use them in every test.

- [ ] **Step 1: Add the factory states**

```php
    public function withoutTrial(): self
    {
        return $this->state(fn (): array => [
            'trial_ends_at' => null,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    public function unlocked(): self
    {
        return $this->state(fn (): array => [
            'access_unlock_until' => now()->addHours(24),
        ]);
    }
```

Note: `unlocked()` references a column that doesn't exist yet (Task 2 adds it). To keep this commit self-contained, add the migration in this task too — see Step 2 — so the factory never references a missing column at runtime.

- [ ] **Step 2: Add the `access_unlock_until` migration**

Create `database/migrations/2026_09_01_000001_add_access_unlock_until_to_organizations_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->timestamp('access_unlock_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn('access_unlock_until');
        });
    }
};
```

- [ ] **Step 3: Add the model cast + property docblock**

In `app/Models/Organization.php`, add to the `@property-read` block (after `trial_ends_at`, keeping alphabetical-ish order):

```php
 * @property-read CarbonInterface|null $access_unlock_until
```

and to `casts()` (after `'trial_ends_at' => 'datetime',`):

```php
            'access_unlock_until' => 'datetime',
```

- [ ] **Step 4: Run the full suite to confirm nothing broke**

Run: `php artisan test --compact`
Expected: PASS (same results as before the change — no test touches the new column yet).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/factories/OrganizationFactory.php database/migrations/2026_09_01_000001_add_access_unlock_until_to_organizations_table.php app/Models/Organization.php
git commit -m "Add access unlock column and trial factory states"
```

---

### Task 2: `GetSubscriptionAccess` action

**Files:**
- Create: `app/Actions/GetSubscriptionAccess.php`
- Test: `tests/Unit/Actions/GetSubscriptionAccessTest.php`

**Interfaces:**
- Consumes: `App\Support\TenantCache` (`remember(name, params, callback, ?ttl)`), `App\Models\Organization` (with Cashier `subscriptions` relation, `onTrial()`, `onGenericTrial()`).
- Produces:
  - `handle(?Organization $organization): array` returning `array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null}` — `null` input returns `null`-verdict shape `['readOnly' => false, 'unlockAvailable' => false, 'unlockedUntil' => null, 'trialEndsAt' => null]`. Tasks 4–6 rely on exactly these keys.
  - The cache TTL rule: `min(900, seconds until trial_ends_at / access_unlock_until, whichever future transition is soonest)`, floored at 1.

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Actions/GetSubscriptionAccessTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\GetSubscriptionAccess;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolve the action with the organization as the acting tenant. The
 * cache keys off Auth::user(), so tests act as a member of the org.
 */
function accessFor(Organization $organization): array
{
    $user = User::factory()->forOrganization($organization)->create();

    Auth::login($user);

    /** @var array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null} */
    return resolve(GetSubscriptionAccess::class)->handle($organization);
}

it('returns a full-access verdict without an organization', function (): void {
    $verdict = resolve(GetSubscriptionAccess::class)->handle(null);

    expect($verdict)->toBe([
        'readOnly' => false,
        'unlockAvailable' => false,
        'unlockedUntil' => null,
        'trialEndsAt' => null,
    ]);
});

it('grants full access while the generic trial runs', function (): void {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->addDays(14),
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse()
        ->and($verdict['trialEndsAt'])->toBe($organization->trial_ends_at->toIso8601String())
        ->and($verdict['unlockedUntil'])->toBeNull()
        ->and($verdict['unlockAvailable'])->toBeFalse();
});

it('marks an expired trial without a subscription as read-only with the unlock available', function (): void {
    $organization = Organization::factory()->expired()->create();

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeTrue()
        ->and($verdict['trialEndsAt'])->toBe($organization->trial_ends_at->toIso8601String())
        ->and($verdict['unlockedUntil'])->toBeNull();
});

it('marks a trial-less unsubscribed organization as read-only', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeTrue()
        ->and($verdict['trialEndsAt'])->toBeNull();
});

it('grants full access for a valid subscription', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    // The checkout stores the subscription with the plan key as its type...
    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_access_1',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse();
});

it('treats a canceled subscription still in its grace period as full access', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_access_2',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(10), // canceled, running to period end...
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse();
});

it('treats a past-due subscription as read-only under Cashier defaults', function (): void {
    $organization = Organization::factory()->withoutTrial()->create();

    $organization->subscriptions()->create([
        'type' => 'pro',
        'stripe_id' => 'sub_access_3',
        'stripe_status' => 'past_due',
        'stripe_price' => 'price_pro',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeTrue();
});

it('grants full access while a 24h unlock is running', function (): void {
    $organization = Organization::factory()->expired()->unlocked()->create();

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeFalse()
        ->and($verdict['unlockedUntil'])->toBe($organization->access_unlock_until->toIso8601String())
        ->and($verdict['unlockAvailable'])->toBeFalse();
});

it('never offers the unlock again once it has been used', function (): void {
    $organization = Organization::factory()->expired()->create([
        'access_unlock_until' => now()->subHour(), // used and expired...
    ]);

    $verdict = accessFor($organization);

    expect($verdict['readOnly'])->toBeTrue()
        ->and($verdict['unlockAvailable'])->toBeFalse();
});

it('caps the cache TTL at the trial end when it is the soonest transition', function (): void {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->addMinutes(5),
    ]);

    $user = User::factory()->forOrganization($organization)->create();
    Auth::login($user);

    resolve(GetSubscriptionAccess::class)->handle($organization);

    // ArrayStore exposes raw storage: find the entry by name fragment
    // (the key embeds a UUID version token)...
    /** @var \Illuminate\Cache\ArrayStore $arrayStore */
    $arrayStore = cache()->store()->getStore();

    $reflection = new ReflectionClass($arrayStore);
    $property = $reflection->getProperty('storage');
    $entries = $property->getValue($arrayStore);

    $accessEntries = array_filter(
        $entries,
        fn (array $entry, string $key): bool => str_contains($key, ':subscription-access:'),
        ARRAY_FILTER_USE_BOTH,
    );

    expect($accessEntries)->not->toBeEmpty();

    $entry = reset($accessEntries);
    $remaining = $entry['expiresAt'] - now()->getPreciseTimestamp(3) / 1000;

    expect($remaining)->toBeGreaterThan(0)->toBeLessThanOrEqual(300);
});

it('caps the cache TTL at the unlock expiry when it is the soonest transition', function (): void {
    $organization = Organization::factory()->expired()->create([
        'access_unlock_until' => now()->addMinutes(10),
    ]);

    $user = User::factory()->forOrganization($organization)->create();
    Auth::login($user);

    resolve(GetSubscriptionAccess::class)->handle($organization);

    /** @var \Illuminate\Cache\ArrayStore $arrayStore */
    $arrayStore = cache()->store()->getStore();

    $reflection = new ReflectionClass($arrayStore);
    $property = $reflection->getProperty('storage');
    $entries = $property->getValue($arrayStore);

    $accessEntries = array_filter(
        $entries,
        fn (array $entry, string $key): bool => str_contains($key, ':subscription-access:'),
        ARRAY_FILTER_USE_BOTH,
    );

    expect($accessEntries)->not->toBeEmpty();

    $entry = reset($accessEntries);
    $remaining = $entry['expiresAt'] - now()->getPreciseTimestamp(3) / 1000;

    expect($remaining)->toBeGreaterThan(0)->toBeLessThanOrEqual(600);
});
```

Both TTL tests use `ARRAY_FILTER_USE_BOTH` with the unused `$entry` parameter so Pest's static analysis doesn't flag the closure signature — keep the two-arg closure exactly as written.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact tests/Unit/Actions/GetSubscriptionAccessTest.php`
Expected: FAIL — `Class "App\Actions\GetSubscriptionAccess" not found`.

- [ ] **Step 3: Implement the action**

Create `app/Actions/GetSubscriptionAccess.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Support\TenantCache;
use Carbon\CarbonInterface;
use Laravel\Cashier\Subscription;

/**
 * The organization's access verdict: full access (valid subscription, running
 * trial, or active unlock) or read-only. One cached entry serves both the
 * enforcement middleware and the shared banner prop, so they can never
 * disagree.
 */
final readonly class GetSubscriptionAccess
{
    private const int TTL_CEILING = 900;

    /**
     * @param  array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null}
     */
    public function __construct(private TenantCache $cache) {}

    /**
     * @return array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null}
     */
    public function handle(?Organization $organization): array
    {
        if (! $organization instanceof Organization) {
            return [
                'readOnly' => false,
                'unlockAvailable' => false,
                'unlockedUntil' => null,
                'trialEndsAt' => null,
            ];
        }

        /** @var array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null} */
        return $this->cache->remember(
            'subscription-access',
            [],
            fn (): array => $this->compute($organization),
            $this->ttl($organization),
        );
    }

    /**
     * @return array{readOnly: bool, unlockAvailable: bool, unlockedUntil: string|null, trialEndsAt: string|null}
     */
    private function compute(Organization $organization): array
    {
        // The checkout creates the subscription with the plan key as its
        // type, so the first row is the plan (the same convention as
        // GetSubscriptionSummary / GetPlanLimits)...
        $validSubscription = $organization->subscriptions
            ->first(fn (Subscription $subscription): bool => $subscription->valid());

        $unlockedUntil = $organization->access_unlock_until;

        $readOnly = $validSubscription === null
            && ! $organization->onGenericTrial()
            && ($unlockedUntil === null || $unlockedUntil->isPast());

        return [
            'readOnly' => $readOnly,
            'unlockAvailable' => $readOnly && $unlockedUntil === null,
            'unlockedUntil' => $unlockedUntil?->isFuture() === true ? $unlockedUntil->toIso8601String() : null,
            'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
        ];
    }

    /**
     * Cap the entry's life at the next known time transition so a cached
     * "full access" verdict can never outlive the moment it could flip.
     */
    private function ttl(Organization $organization): int
    {
        $horizon = now()->addSeconds(self::TTL_CEILING);

        if ($organization->trial_ends_at?->isFuture() === true) {
            $horizon = $horizon->min($organization->trial_ends_at);
        }

        if ($organization->access_unlock_until?->isFuture() === true) {
            $horizon = $horizon->min($organization->access_unlock_until);
        }

        $ttl = $horizon->diffInSeconds(now());

        return max(1, (int) $ttl);
    }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Unit/Actions/GetSubscriptionAccessTest.php`
Expected: PASS (12 tests).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/GetSubscriptionAccess.php tests/Unit/Actions/GetSubscriptionAccessTest.php
git commit -m "Add GetSubscriptionAccess verdict action with transition-aware TTL"
```

---

### Task 3: `OrganizationReadOnly` exception + `EnsureOrganizationNotReadOnly` middleware

**Files:**
- Create: `app/Exceptions/OrganizationReadOnly.php`
- Create: `app/Http/Middleware/EnsureOrganizationNotReadOnly.php`
- Modify: `bootstrap/app.php:20-43` (middleware alias + exception renderer)
- Test: `tests/Unit/Middleware/EnsureOrganizationNotReadOnlyTest.php`

**Interfaces:**
- Consumes: `GetSubscriptionAccess` (Task 2's exact return keys).
- Produces: `EnsureOrganizationNotReadOnly::handle(Request $request, Closure $next): Response`; alias name `not-read-only` registered in `bootstrap/app.php`. Task 4 attaches it to routes; Task 5's renderer expects the exception class `App\Exceptions\OrganizationReadOnly` with a translatable message.

- [ ] **Step 1: Write the failing middleware tests**

Create `tests/Unit/Middleware/EnsureOrganizationNotReadOnlyTest.php`:

```php
<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureOrganizationNotReadOnly;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

    $response = (new EnsureOrganizationNotReadOnly())->handle($request, function () use (&$passed): string {
        $passed = true;

        return 'next';
    });

    expect($passed)->toBeTrue()
        ->and($response)->toBe('next');
});

it('blocks mutating requests for a read-only organization', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = read_only_middleware_user($organization);

    $request = Request::create('/clients', 'POST');
    $request->setUserResolver(fn () => $user);

    // The middleware reads the route name from the request's route
    // resolver; bind a real route so getName() returns 'clients.store'...
    $route = Route::getRoutes()->addRoute(['POST'], '/clients', ['uses' => fn () => 'ok', 'as' => 'clients.store']);
    $request->setRouteResolver(fn () => $route);

    (new EnsureOrganizationNotReadOnly())->handle($request, fn (): string => 'next');
})->throws(App\Exceptions\OrganizationReadOnly::class);

it('exempts billing routes from the block', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = read_only_middleware_user($organization);

    $request = Request::create('/settings/billing', 'POST');
    $request->setUserResolver(fn () => $user);

    $route = Route::getRoutes()->addRoute(['POST'], '/settings/billing', ['uses' => fn () => 'ok', 'as' => 'billing.checkout']);
    $request->setRouteResolver(fn () => $route);

    $passed = false;

    $response = (new EnsureOrganizationNotReadOnly())->handle($request, function () use (&$passed): string {
        $passed = true;

        return 'next';
    });

    expect($passed)->toBeTrue()
        ->and($response)->toBe('next');
});
```

The test file needs `use Illuminate\Support\Facades\Route;` — but note `Route::getRoutes()->addRoute(...)` is called on the facade root (`Illuminate\Routing\RoutingServiceProvider`'s router), which is available in tests without the facade mock. If `addRoute` on a fresh app is inconvenient, the equivalent is `new Illuminate\Routing\Route(['POST'], '/clients', ['as' => 'clients.store'])` bound via `setRouteResolver`. Use whichever runs; the assertion contract is fixed: POST + non-billing named route + expired-trial org user → `OrganizationReadOnly`; POST + `billing.*` route → passes.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact tests/Unit/Middleware/EnsureOrganizationNotReadOnlyTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Create the exception**

Create `app/Exceptions/OrganizationReadOnly.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a read-only organization (expired trial, no valid
 * subscription, no active unlock) attempts a mutating request. Rendered
 * back to the user as an upgrade prompt instead of a generic error.
 */
final class OrganizationReadOnly extends RuntimeException
{
    //
}
```

- [ ] **Step 4: Create the middleware**

Create `app/Http/Middleware/EnsureOrganizationNotReadOnly.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\GetSubscriptionAccess;
use App\Exceptions\OrganizationReadOnly;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks data mutations for organizations without valid access (expired
 * trial, no valid subscription, no active unlock). Reads always pass; the
 * billing routes stay open — they are the way out.
 */
final readonly class EnsureOrganizationNotReadOnly
{
    private const array SAFE_VERBS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private GetSubscriptionAccess $getSubscriptionAccess) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), self::SAFE_VERBS, true)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'billing.')) {
            return $next($request);
        }

        $organization = $request->user()?->organization;

        if ($organization === null) {
            return $next($request);
        }

        $verdict = $this->getSubscriptionAccess->handle($organization);

        if ($verdict['readOnly']) {
            throw new OrganizationReadOnly(__('statuses.read_only_blocked'));
        }

        return $next($request);
    }
}
```

Note: `statuses.read_only_blocked` is defined in Task 5 — create the lang keys in this task instead (see Step 6) so this commit never references a missing key. Timing: the middleware test in Step 1 doesn't assert the message, so it passes either way, but the key must exist before any feature test runs.

- [ ] **Step 5: Register the alias in bootstrap/app.php**

In `bootstrap/app.php`, inside `withMiddleware`, after the `validateCsrfTokens` call (line 24), add:

```php
        $middleware->alias([
            'not-read-only' => \App\Http\Middleware\EnsureOrganizationNotReadOnly::class,
        ]);
```

- [ ] **Step 6: Add the blocked-status lang keys**

`lang/en/statuses.php` — add after the `'plan_limit'` line:

```php
    'read_only_blocked' => 'Your trial has ended. Upgrade your plan to keep managing your data — everything stays viewable meanwhile.',
```

`lang/fr/statuses.php` — add after the `'plan_limit'` line:

```php
    'read_only_blocked' => 'Votre essai est terminé. Passez à un forfait payant pour continuer à gérer vos données — tout reste consultable en attendant.',
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Unit/Middleware/EnsureOrganizationNotReadOnlyTest.php`
Expected: PASS.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exceptions/OrganizationReadOnly.php app/Http/Middleware/EnsureOrganizationNotReadOnly.php bootstrap/app.php lang/en/statuses.php lang/fr/statuses.php tests/Unit/Middleware/EnsureOrganizationNotReadOnlyTest.php
git commit -m "Add read-only middleware blocking mutations for expired organizations"
```

---

### Task 4: Wire the middleware into the app route group

**Files:**
- Modify: `routes/web.php:35` — `Route::middleware(['auth', 'verified:login-challenge.show'])->group(...)` becomes `['auth', 'verified:login-challenge.show', 'not-read-only']`
- Test: `tests/Feature/ReadOnlyAccessTest.php`

**Interfaces:**
- Consumes: alias `not-read-only` (Task 3), factory states (Task 1).
- Produces: mutating requests by read-only orgs redirect back with `errors.readOnly`; billing mutations and all GETs still work. Task 6's banner test relies on this behavior existing.

- [ ] **Step 1: Write the failing feature tests**

Create `tests/Feature/ReadOnlyAccessTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\Season;

beforeEach(function (): void {
    $this->organization = Organization::factory()->expired()->create();
    $this->admin = User::factory()->admin()->forOrganization($this->organization)->create();
});

it('allows read-only organizations to view pages', function (): void {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk();
});

it('blocks creating clients while read-only', function (): void {
    $response = $this->actingAs($this->admin)
        ->fromRoute('clients.create')
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Blocked',
            'lastName' => 'Create',
            'email' => 'blocked@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ]);

    $response->assertRedirectToRoute('clients.create')
        ->assertSessionHas('errors');

    expect(Client::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(0);
});

it('blocks creating users while read-only', function (): void {
    $response = $this->actingAs($this->admin)
        ->fromRoute('users.create')
        ->post(route('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'user',
        ]);

    $response->assertRedirectToRoute('users.create');

    expect(User::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(1); // only the admin...
});

it('blocks creating contracts while read-only', function (): void {
    $client = Client::factory()->forOrganization($this->organization)->create();

    $response = $this->actingAs($this->admin)
        ->fromRoute('contracts.create')
        ->post(route('contracts.store'), [
            'clientId' => $client->id,
            'season' => Season::current()->label,
            'services' => [
                ['label' => 'Déneigement entrée', 'amount' => 35000],
            ],
            'payments' => [
                'deposit' => 15000,
                'first' => 10000,
                'second' => 10000,
                'balance' => 10000,
            ],
        ]);

    $response->assertRedirectToRoute('contracts.create')
        ->assertSessionHas('errors');

    expect($client->contracts()->count())->toBe(0);
});

it('allows billing checkout while read-only', function (): void {
    $this->organization->forceFill(['stripe_id' => 'cus_test_123'])->save();
    config(['plans.starter.prices.monthly' => 'price_test_monthly']);

    bindFakeStripeClient();

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => 'monthly'])
        ->assertRedirect('https://checkout.stripe.com/unused');
});

it('still blocks reads for guests before the middleware runs', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
```

The `bindFakeStripeClient()` helper lives in `tests/Feature/BillingControllerTest.php` — Pest loads it from any file in the suite, so the helper is available here without redefinition.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact tests/Feature/ReadOnlyAccessTest.php`
Expected: The three "blocks" tests FAIL (requests succeed/redirect without `errors` — mutation goes through); `allows` tests may already pass.

- [ ] **Step 3: Add the middleware to the route group**

In `routes/web.php:35`, change:

```php
Route::middleware(['auth', 'verified:login-challenge.show', 'not-read-only'])->group(function (): void {
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Feature/ReadOnlyAccessTest.php`
Expected: PASS (6 tests).

- [ ] **Step 5: Run the neighboring suites to confirm no regression**

Run: `php artisan test --compact tests/Feature/PlanLimitTest.php tests/Feature/CacheInvalidationTest.php tests/Feature/OrganizationScopingTest.php tests/Feature/BillingControllerTest.php`
Expected: PASS — these suites use default `Organization::factory()` (trial in the future), so the middleware never trips.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add routes/web.php tests/Feature/ReadOnlyAccessTest.php
git commit -m "Enforce read-only access on app route group"
```

---

### Task 5: Exception renderer + `GrantAccessUnlock` action + `billing.unlock` route

**Files:**
- Modify: `bootstrap/app.php:33-43` — add the renderer next to `PlanLimitExceeded`
- Create: `app/Actions/GrantAccessUnlock.php`
- Modify: `app/Http/Controllers/BillingController.php` — add `unlock()`
- Modify: `routes/web.php:39-47` — add the unlock route inside the `can:manage-billing` nest
- Test: `tests/Unit/Actions/GrantAccessUnlockTest.php` (fold into Task 4's feature test for the HTTP path)

**Interfaces:**
- Consumes: `GetSubscriptionAccess` (Task 2) for the eligibility re-check; `FlushOrganizationCache`; `OrganizationReadOnly` (Task 3).
- Produces: route name `billing.unlock` (`POST settings/billing/unlock`, throttled `6,1`, `can:manage-billing`); `GrantAccessUnlock::handle(User $user): void`; renderer converts `OrganizationReadOnly` into `back()->withErrors(['readOnly' => message])` for Inertia requests and `back()->with('status', message)` otherwise. Task 6's banner links to it.

- [ ] **Step 1: Write the failing unit tests**

Create `tests/Unit/Actions/GrantAccessUnlockTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\GrantAccessUnlock;
use App\Exceptions\OrganizationReadOnly;
use App\Models\Organization;
use App\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->expired()->create();
    $this->admin = User::factory()->admin()->forOrganization($this->organization)->create();
});

it('grants a 24h unlock to a read-only organization', function (): void {
    $this->actingAs($this->admin);

    resolve(GrantAccessUnlock::class)->handle($this->admin);

    expect($this->organization->fresh()->access_unlock_until->toIso8601String())
        ->toBe(now()->addHours(24)->toIso8601String());
});

it('refuses to grant the unlock twice', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->addHours(24)])->save();

    resolve(GrantAccessUnlock::class)->handle($this->admin);
})->throws(OrganizationReadOnly::class);

it('refuses the unlock when the org already has full access', function (): void {
    $organization = Organization::factory()->create(); // trial running...

    $admin = User::factory()->admin()->forOrganization($organization)->create();

    $this->actingAs($admin);

    resolve(GrantAccessUnlock::class)->handle($admin);
})->throws(OrganizationReadOnly::class);
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact tests/Unit/Actions/GrantAccessUnlockTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement the action**

Create `app/Actions/GrantAccessUnlock.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\OrganizationReadOnly;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Grants a read-only organization its one-time 24h full-access unlock.
 * Available regardless of the cause (expired trial or failed payment),
 * but only once per organization lifetime.
 */
final readonly class GrantAccessUnlock
{
    public function __construct(
        private GetSubscriptionAccess $getSubscriptionAccess,
        private FlushOrganizationCache $flushCache,
    ) {}

    public function handle(User $admin): void
    {
        /** @var Organization $organization */
        $organization = $admin->organization;

        DB::transaction(function () use ($organization): void {
            $verdict = $this->getSubscriptionAccess->handle($organization);

            // The unlock exists only for organizations that cannot act;
            // one grant per lifetime...
            if (! $verdict['readOnly'] || $verdict['unlockedUntil'] !== null) {
                throw new OrganizationReadOnly(__('statuses.unlock_used'));
            }

            $organization->forceFill([
                'access_unlock_until' => now()->addHours(24),
            ])->save();
        });

        $this->flushCache->handle($organization->id);
    }
}
```

- [ ] **Step 4: Add the controller method + route**

`app/Http/Controllers/BillingController.php` — add after `update()` (line 52-57):

```php
    public function unlock(#[CurrentUser] User $user, GrantAccessUnlock $action): RedirectResponse
    {
        $action->handle($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('statuses.unlock_granted'),
        ]);

        return to_route('billing.index');
    }
```

Add imports at the top of the controller:

```php
use App\Actions\GrantAccessUnlock;
use Illuminate\Support\Facades\Inertia;
```

`routes/web.php` — inside the `can:manage-billing` group, after the `billing.portal` route:

```php
        Route::post('settings/billing/unlock', [BillingController::class, 'unlock'])
            ->middleware('throttle:6,1')
            ->name('billing.unlock');
```

- [ ] **Step 5: Add the unlock lang keys**

`lang/en/statuses.php`:

```php
    'unlock_used' => 'The 24-hour unlock has already been used for this organization.',
    'unlock_granted' => 'Full access restored for 24 hours.',
```

`lang/fr/statuses.php`:

```php
    'unlock_used' => 'Le déblocage de 24 heures a déjà été utilisé pour cette organisation.',
    'unlock_granted' => 'Accès complet rétabli pendant 24 heures.',
```

- [ ] **Step 6: Add the exception renderer**

In `bootstrap/app.php`, inside `withExceptions`, after the `PlanLimitExceeded` renderer:

```php
        // A read-only organization (expired trial, lapsed subscription) gets
        // the same redirect-back-with-prompt treatment as plan limits...
        $exceptions->render(function (OrganizationReadOnly $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['readOnly' => $exception->getMessage()]);
            }

            return back()->with('status', $exception->getMessage());
        });
```

Add the import next to the existing `App\Exceptions\PlanLimitExceeded` import:

```php
use App\Exceptions\OrganizationReadOnly;
```

- [ ] **Step 7: Run all new unit tests**

Run: `php artisan test --compact tests/Unit/Actions/GrantAccessUnlockTest.php`
Expected: PASS (3 tests).

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/GrantAccessUnlock.php app/Http/Controllers/BillingController.php routes/web.php bootstrap/app.php lang/en/statuses.php lang/fr/statuses.php tests/Unit/Actions/GrantAccessUnlockTest.php
git commit -m "Add one-time 24h access unlock via billing page"
```

---

### Task 6: Feature tests for the unlock lifecycle

**Files:**
- Test: extend `tests/Feature/ReadOnlyAccessTest.php` (Task 4's file)

**Interfaces:**
- Consumes: route `billing.unlock` (Task 5), renderer (Task 5), factory states (Task 1).
- Produces: proof of the full lifecycle — unlock grants access, expiry re-locks, second grant refused, non-admin 403.

- [ ] **Step 1: Append the lifecycle tests to `tests/Feature/ReadOnlyAccessTest.php`**

Inside the file (they use the same `beforeEach`):

```php
it('lets an admin unlock full access for 24 hours', function (): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.unlock'))
        ->assertRedirectToRoute('billing.index');

    expect($this->organization->fresh()->access_unlock_until)
        ->not->toBeNull();

    // Mutations work again immediately...
    $this->actingAs($this->admin)
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Back',
            'lastName' => 'Online',
            'email' => 'back@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ])->assertRedirect();

    expect(Client::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(1);
});

it('re-locks the organization once the unlock expires', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->subHour()])->save();

    // The cached verdict was capped at the unlock expiry, so by now it
    // has expired and the next request recomputes read-only...
    $this->travelTo(now()->addHours(2));

    $response = $this->actingAs($this->admin)
        ->fromRoute('clients.create')
        ->post(route('clients.store'), [
            'type' => 'individual',
            'firstName' => 'Locked',
            'lastName' => 'Again',
            'email' => 'locked@example.com',
            'phoneDay' => '418-555-0123',
            'addressStreet' => '123 Rue',
            'addressCity' => 'Québec',
            'addressPostalCode' => 'G1K 0A1',
            'addressCountry' => 'Canada',
        ]);

    $response->assertRedirectToRoute('clients.create');

    expect(Client::query()->withoutGlobalScopes()
        ->where('organization_id', $this->organization->id)
        ->count())->toBe(0);
});

it('refuses a second unlock', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->subHour()])->save();

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.unlock'))
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHas('errors');
});

it('forbids non-admins from unlocking', function (): void {
    $worker = User::factory()->forOrganization($this->organization)->create();

    $this->actingAs($worker)
        ->post(route('billing.unlock'))
        ->assertForbidden();
});
```

These tests depend on Task 5's route and renderer. The "re-locks" test deliberately avoids any `assertInertia` on `auth.access` — Task 7 adds that assertion in Step 10 after the shared prop exists, keeping every commit green.

- [ ] **Step 2: Run the tests to verify they pass**

Run: `php artisan test --compact tests/Feature/ReadOnlyAccessTest.php`
Expected: PASS (10 tests — 6 from Task 4 + 4 new).

- [ ] **Step 3: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/ReadOnlyAccessTest.php
git commit -m "Cover the unlock lifecycle in feature tests"
```

---

### Task 7: Share the verdict as `auth.access` + `TrialBanner`

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php:20,55` — inject `GetSubscriptionAccess`, share `access`
- Modify: `resources/js/types/auth.ts` — add `SubscriptionAccess` type + `access` on `Auth`
- Create: `resources/js/components/trial-banner.tsx`
- Modify: `resources/js/layouts/app/app-sidebar-layout.tsx:14-16` — mount the banner
- Modify: `resources/js/lang/en.ts` + `resources/js/lang/fr.ts` — add banner keys under `billing`
- Test: `tests/Unit/Middleware/HandleInertiaRequestsTest.php` (extend), `tests/Feature/ReadOnlyAccessTest.php` (assertInertia block)

**Interfaces:**
- Consumes: `GetSubscriptionAccess` (Task 2) verdict keys; Wayfinder route functions `index` from `@/routes/billing` (existing) and `unlock` (generated after Task 5's route by `wayfinder:generate` — run it before building the component); `auth.subscription` (existing shared prop) for the banner's copy-variant rule.
- Produces: shared prop `auth.access: { readOnly: boolean; unlockAvailable: boolean; unlockedUntil: string | null; trialEndsAt: string | null }`; component `<TrialBanner />` with two copy variants (trial-ended vs. payment-issue, keyed off `auth.subscription !== null`).

- [ ] **Step 1: Write the failing shared-props test**

Append to `tests/Unit/Middleware/HandleInertiaRequestsTest.php`:

```php
it('shares the access verdict for an organization member', function (): void {
    $organization = Organization::factory()->expired()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $middleware = new HandleInertiaRequests(
        resolve(App\Actions\GetSubscriptionSummary::class),
        resolve(App\Actions\GetSubscriptionAccess::class),
    );

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['access'])->toBe([
        'readOnly' => true,
        'unlockAvailable' => true,
        'unlockedUntil' => null,
        'trialEndsAt' => $organization->trial_ends_at->toIso8601String(),
    ]);
});
```

Also update the existing tests' constructor calls `new HandleInertiaRequests(resolve(GetSubscriptionSummary::class))` to the two-argument form.

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --compact tests/Unit/Middleware/HandleInertiaRequestsTest.php`
Expected: FAIL — `__construct()` takes 1 argument, 2 given (and no `access` key).

- [ ] **Step 3: Implement the shared prop**

`app/Http/Middleware/HandleInertiaRequests.php` — add the dependency and the key:

```php
use App\Actions\GetSubscriptionAccess;
use App\Actions\GetSubscriptionSummary;

final class HandleInertiaRequests extends Middleware
{
    // ...

    public function __construct(
        private readonly GetSubscriptionSummary $getSubscriptionSummary,
        private readonly GetSubscriptionAccess $getSubscriptionAccess,
    ) {}

    // in share(), inside 'auth' => [ ... ], after 'subscription' => ...:
                'subscription' => $this->getSubscriptionSummary->handle($user?->organization),
                'access' => $this->getSubscriptionAccess->handle($user?->organization),
```

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test --compact tests/Unit/Middleware/HandleInertiaRequestsTest.php`
Expected: PASS.

- [ ] **Step 5: Add the frontend type**

`resources/js/types/auth.ts` — add below the `Subscription` type and extend `Auth`:

```ts
export type SubscriptionAccess = {
    readOnly: boolean;
    unlockAvailable: boolean;
    unlockedUntil: string | null;
    trialEndsAt: string | null;
};

export type Auth = {
    user: User;
    organization?: Organization | null;
    subscription?: Subscription | null;
    access?: SubscriptionAccess | null;
    can?: {
        manageUsers?: boolean;
        manageClients?: boolean;
        manageContracts?: boolean;
        manageBilling?: boolean;
    };
};
```

- [ ] **Step 6: Add the frontend copy**

`resources/js/lang/en.ts` — inside the existing `billing: { ... }` object, add (the `Payment` variants cover the case where a subscription row exists but is invalid — past-due or canceled):

```ts
        readOnlyTitle: 'Your trial has ended',
        readOnlyBody:
            'Your data is read-only — upgrade to keep managing your organization.',
        readOnlyUpgrade: 'Upgrade now',
        readOnlyUnlock: 'Unlock 24 hours (one-time)',
        readOnlyPaymentTitle: 'Payment issue',
        readOnlyPaymentBody:
            'Your subscription is not currently active. Your data is read-only until payment is restored.',
```

`resources/js/lang/fr.ts` — inside `billing: { ... }`:

```ts
        readOnlyTitle: 'Votre essai est terminé',
        readOnlyBody:
            'Vos données sont en lecture seule — passez à un forfait pour continuer à gérer votre organisation.',
        readOnlyUpgrade: 'Passer à un forfait',
        readOnlyUnlock: 'Débloquer 24 heures (unique)',
        readOnlyPaymentTitle: 'Problème de paiement',
        readOnlyPaymentBody:
            'Votre abonnement n\u2019est pas actif actuellement. Vos données sont en lecture seule jusqu\u2019au rétablissement du paiement.',
```

- [ ] **Step 7: Generate the Wayfinder unlock route**

Run: `php artisan wayfinder:generate --no-interaction`

Expected: `resources/js/routes/billing/index.ts` now exports `unlock` (`POST settings/billing/unlock`).

- [ ] **Step 8: Build the banner component**

Create `resources/js/components/trial-banner.tsx`:

```tsx
import { LockIcon } from 'lucide-react';
import { Form, usePage } from '@inertiajs/react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import { index, unlock } from '@/routes/billing';

/**
 * Persistent read-only state banner shown above every app page while the
 * organization has no valid access (expired trial, lapsed subscription).
 * The unlock button is the one-time 24h escape hatch for admins.
 */
export default function TrialBanner() {
    const { auth } = usePage().props;
    const { t } = useLocale();

    if (!auth.access?.readOnly) {
        return null;
    }

    // Copy variant rule: any subscription row ever created means the org
    // got here through a payment problem, not an expired trial...
    const hasSubscription = auth.subscription !== null && auth.subscription !== undefined;

    const title = hasSubscription ? t.billing.readOnlyPaymentTitle : t.billing.readOnlyTitle;
    const body = hasSubscription ? t.billing.readOnlyPaymentBody : t.billing.readOnlyBody;

    return (
        <Alert>
            <LockIcon />
            <AlertTitle>{title}</AlertTitle>
            <AlertDescription>
                <p>{body}</p>
                <div className="mt-2 flex flex-wrap gap-2">
                    <Button asChild size="sm">
                        <a href={index()}>{t.billing.readOnlyUpgrade}</a>
                    </Button>
                    {auth.can?.manageBilling && auth.access.unlockAvailable && (
                        <Form {...unlock.form()}>
                            <Button type="submit" size="sm" variant="outline">
                                {t.billing.readOnlyUnlock}
                            </Button>
                        </Form>
                    )}
                </div>
            </AlertDescription>
        </Alert>
    );
}
```

- [ ] **Step 9: Mount it in the app shell**

`resources/js/layouts/app/app-sidebar-layout.tsx`:

```tsx
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import TrialBanner from '@/components/trial-banner';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <TrialBanner />
                {children}
            </AppContent>
        </AppShell>
    );
}
```

- [ ] **Step 10: Restore the banner assertions to the feature test**

In `tests/Feature/ReadOnlyAccessTest.php`, the "re-locks" test's Inertia assertion (from Task 6's note) — add/confirm:

```php
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.access.readOnly', true)
            ->where('auth.access.unlockAvailable', false));
```

Also add a first-visit assertion to the "allows read-only organizations to view pages" test:

```php
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.access.readOnly', true)
            ->where('auth.access.unlockAvailable', true));
```

- [ ] **Step 11: Run all the affected tests**

Run: `php artisan test --compact tests/Unit/Middleware/HandleInertiaRequestsTest.php tests/Feature/ReadOnlyAccessTest.php`
Expected: PASS.

- [ ] **Step 12: Frontend checks**

Run: `npm run test:types && npm run test:lint`
Expected: PASS (no TS or lint errors).

- [ ] **Step 13: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware/HandleInertiaRequests.php resources/js/types/auth.ts resources/js/components/trial-banner.tsx resources/js/layouts/app/app-sidebar-layout.tsx resources/js/lang/en.ts resources/js/lang/fr.ts resources/js/routes tests/Unit/Middleware/HandleInertiaRequestsTest.php tests/Feature/ReadOnlyAccessTest.php
git commit -m "Show persistent read-only banner with unlock for admins"
```

---

### Task 8: Webhook invalidates the access verdict (integration proof)

**Files:**
- Test: extend `tests/Feature/CacheInvalidationTest.php`

**Interfaces:**
- Consumes: `auth.access` shared prop (Task 7), the existing webhook conventions in that file (`postJson` raw Stripe payloads to `route('cashier.webhook')`), factory `expired()` (Task 1).
- Produces: proof that a Stripe subscription event flips a read-only org to full access without manual flush — end-to-end coverage of the spec's event-transition invalidation.

- [ ] **Step 1: Append the webhook transition test**

To `tests/Feature/CacheInvalidationTest.php` (uses the file's existing `$this->admin` / `beforeEach` conventions — verify the variable names in the file before writing):

```php
it('restores full access when a Stripe subscription becomes active', function (): void {
    $this->admin->organization->forceFill([
        'trial_ends_at' => now()->subDay(),
        'stripe_id' => 'cus_test_123',
    ])->save();

    // Seed a read-only verdict...
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.access.readOnly', true));

    // A subscription update webhook for this organization's customer,
    // shaped as Stripe sends it (Cashier reads items.data[0])...
    $this->postJson(route('cashier.webhook'), [
        'id' => 'evt_access',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_access',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_access',
                            'price' => ['id' => 'price_test', 'product' => 'prod_test'],
                            'quantity' => 1,
                        ],
                    ],
                ],
                'metadata' => ['name' => 'starter'],
            ],
        ],
    ])->assertOk();

    // The verdict recomputed from the flushed cache: mutations work again...
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.access.readOnly', false));
});
```

- [ ] **Step 2: Run it**

Run: `php artisan test --compact tests/Feature/CacheInvalidationTest.php`
Expected: PASS (the webhook handler flushes the org cache — `StripeWebhookController:34-56` — so the second dashboard request recomputes `subscription-access`).

- [ ] **Step 3: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/CacheInvalidationTest.php
git commit -m "Prove webhook events restore access via cache invalidation"
```

---

### Task 9: Full-suite verification + docs note

**Files:**
- No new code files. Final gate.

- [ ] **Step 1: Run the complete test suite**

Run: `php artisan test --compact`
Expected: PASS — entire suite, including Browser tests.

- [ ] **Step 2: Frontend gates**

Run: `npm run test:types && npm run test:lint`
Expected: PASS.

- [ ] **Step 3: Confirm no dirty files**

Run: `git status --short` (excluding `screenshots/` and `tests/Browser/Screenshots/`, which predate this work)
Expected: clean tree, all work committed.

- [ ] **Step 4: Remind the user to rebuild frontend assets**

If the user doesn't see the banner in their browser: `npm run build` (or `npm run dev` / `composer run dev`).

---

## Self-Review (completed)

**Spec coverage check:**

| Spec section | Task(s) |
|---|---|
| State model (`GetSubscriptionAccess`, `valid()`, unlock expiry, trial boundary) | 2 |
| Middleware + safe verbs + `billing.` exemption | 3, 4 |
| `OrganizationReadOnly` exception + renderer | 3, 5 |
| `access_unlock_until` column + cast | 1 |
| `billing.unlock` route + `GrantAccessUnlock` | 5 |
| Banner shared prop `auth.access` | 7 |
| Banner component + billing-page CTA + unlock button (admins only) | 7 |
| EN/FR copy | 3, 5, 7 |
| Transition-aware cache TTL (trial end, unlock expiry, 900s ceiling) | 2 |
| Stripe webhook + unlock-action flushes (event transitions) | 2 (action flush), 8 (webhook proof) |
| Factory states (`expired`, `unlocked`) | 1 |
| Tests: unit state machine + TTL caps | 2 |
| Tests: GET pass / POST blocked / billing allowed / unlock lifecycle / non-admin 403 | 4, 6 |
| Profile/password routes untouched (writability preserved) | 4 (route group scoping — no change needed, they're outside the group) |
| No scheduled jobs needed | Global constraints note |

Gap found and fixed during self-review: the spec's banner-copy variant rule ("any subscription row exists → payment-issue copy") initially had no implementation. Fixed in Task 7: `readOnlyPaymentTitle` / `readOnlyPaymentBody` keys in both locales (Step 6) and the `hasSubscription` copy switch in the `TrialBanner` component (Step 8). Also fixed: Task 3 Step 1 initially used `Route::shouldReceive` (a facade mock the route instance doesn't support) — replaced with `Route::getRoutes()->addRoute(...)` + `setRouteResolver`, plus an added billing-exemption middleware test; Task 2 Step 1 contained a leftover no-op line (`cache()->store()->get('irrelevant')`) — removed; Task 6's "re-locks" test initially asserted `auth.access` before Task 7 creates the shared prop — the assertion moved to Task 7 Step 10 so every commit stays green.

**Type consistency check:** `access` key shape `{readOnly, unlockAvailable, unlockedUntil, trialEndsAt}` is identical across: `GetSubscriptionAccess` PHPDoc (Task 2), middleware test expectations (Tasks 4/6/8), `HandleInertiaRequests` share (Task 7), `SubscriptionAccess` TS type (Task 7), `TrialBanner` reads (Task 7). Lang keys `read_only_blocked` / `unlock_used` / `unlock_granted` (server, Tasks 3/5) and `readOnlyTitle` / `readOnlyBody` / `readOnlyUpgrade` / `readOnlyUnlock` / `readOnlyPaymentTitle` / `readOnlyPaymentBody` (frontend, Task 7) each defined once in both locales. Route name `billing.unlock` consistent across Task 5 route, Task 5/6 tests, Task 7 Wayfinder import.

**Placeholder scan:** none — every step has runnable code or an exact command. The two implementer notes kept (route-resolver construction in Task 3 Step 1, cross-file helper provenance in Task 4 Step 1) document concrete alternatives with the fixed assertion contract, not missing design; each resolves within its own task.
