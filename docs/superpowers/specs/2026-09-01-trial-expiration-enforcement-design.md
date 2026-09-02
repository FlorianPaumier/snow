# Trial Expiration Enforcement — Design

Date: 2026-09-01
Status: Approved (in-chat design review)

## Problem

`trial_ends_at` is set at signup (`app/Actions/CreateOrganization.php:26-29`) but nothing ever checks it — no middleware, gate, or scheduled job. The only subscription-related enforcement in the app is the client/user count limits (`CreateUser`/`CreateClient` → `PlanLimitExceeded`). An organization that never pays therefore retains full access forever; expired-trial orgs silently fall back to starter plan limits (`app/Actions/GetPlanLimits.php:44-56`).

## Decisions (approved by product owner)

1. **Blocking model: read-only.** When an org has no valid access state, all data stays viewable (dashboard, clients, contracts, payments) but every create/update/delete is blocked server-side. Billing and settings remain reachable. A persistent banner explains the state and links to upgrade.
2. **Enforcement placement: route middleware.** One middleware on the main app route group blocks mutating verbs. (Alternatives considered and rejected: gate + `can:` restructuring — 403 UX and route-file churn; per-action asserts — ~10 call sites to maintain, easy to miss one.)
3. **No automatic grace period.** Read-only begins the moment the trial lapses.
4. **One-time 24h admin unlock.** Any admin of a read-only org can, once per organization (ever), grant themselves 24h of full access via a button on the billing page. No offer window — the button remains available indefinitely until used. Covers both expired trials and later payment failures (a dunning escape hatch).
5. **"Valid paid subscription" = Cashier `valid()`** with this app's Cashier defaults (`deactivatePastDue = true`, never overridden): `active() || onTrial() || onGracePeriod()`. A past-due (failed charge) subscription is **not** valid → read-only immediately; access restores automatically via the Stripe webhook when a retry succeeds.
6. **Caching: transition-aware cached verdict.** One cached verdict serves both enforcement and the UI. Its TTL is capped at the next known time-based transition, and all event-based transitions already flush the org cache.

## State model

A new Action, `GetSubscriptionAccess`, is the single source of truth for access state.

```php
array{
    readOnly: bool,           // true when org may not mutate data
    unlockAvailable: bool,    // true when the one-time unlock may be granted (org is read-only and never used it)
    unlockedUntil: ?string,   // ISO timestamp while a 24h unlock is running, else null
    trialEndsAt: ?string,     // for banner copy; null when org never had a trial
}
```

**Full access** when any of:

- `trial_ends_at` is in the future (Cashier `onGenericTrial()` semantics — same boundary as the display fix in `GetSubscriptionSummary`), or
- any subscription row passes Cashier `Subscription::valid()` (`active() || onTrial() || onGracePeriod()`; includes canceled-but-running-to-period-end and excludes incomplete-expired, unpaid, and — per this app's defaults — past-due), or
- `access_unlock_until` is in the future.

Otherwise **read-only**.

`unlockAvailable` is true only when the org is read-only **and** `access_unlock_until` is null (never granted before). One grant per organization, lifetime.

The verdict object is computed by `GetSubscriptionAccess` and consumed by three callers (see Caching): the middleware, `HandleInertiaRequests`, and the unlock action (to re-check eligibility). No other code path derives access state.

## Server enforcement

### Middleware

New `app/Http/Middleware/EnsureOrganizationNotReadOnly.php`:

- Registered in `bootstrap/app.php` with an alias (e.g. `not-read-only`), appended to the existing `auth` + `verified:login-challenge.show` middleware group in `routes/web.php:35` (the whole org-scoped app).
- Safe verbs — `GET`, `HEAD`, `OPTIONS` — pass through untouched: read-only access falls out for free and costs one cache read.
- Mutating verbs — `POST`, `PUT`, `PATCH`, `DELETE` — allowed when the route is a billing route (`billing.index`, `billing.checkout`, `billing.portal`, `billing.unlock` — identified by name prefix `billing.`; checkout/portal/unlock are the escape route), otherwise throw the new exception.
- Middleware runs after auth so `Auth::user()->organization` is available (same dependency the global `OrganizationScope` uses). Unauthenticated/unverified requests are already rejected by the outer middleware.

### Exception + renderer

New `app/Exceptions/OrganizationReadOnly.php` (plain exception, mirroring `PlanLimitExceeded`).

Renderer added alongside the existing one in `bootstrap/app.php` (currently `:33-42`), same UX pattern:

- Inertia request → `back()->withErrors(['readOnly' => $message])`
- otherwise → `back()->with('status', $message)`

### Routes that stay writable

- All `billing.*` routes (checkout, portal, unlock) — that is the escape route.
- Self-profile, password, appearance, language routes live in the separate `auth`-only group (`routes/web.php:103`) and are untouched — users can always manage their own account.
- All GETs everywhere.

## One-time unlock

- Migration: nullable timestamp `access_unlock_until` on `organizations` (cast to `datetime` on the model).
- Route: `POST settings/billing/unlock` → name `billing.unlock`, nested inside the existing billing route group (`can:manage-billing`, throttle `6,1`).
- Controller: `BillingController@unlock` — `app/Http/Controllers/BillingController.php` already owns checkout/portal, so the unlock endpoint joins it rather than a new controller.
- Action: `app/Actions/GrantAccessUnlock.php` — guards: org is read-only and `access_unlock_until` is null (re-check via `GetSubscriptionAccess`, not just the column); then sets `access_unlock_until = now()->addHours(24)` inside `DB::transaction()`, and flushes the org cache (`FlushOrganizationCache`, outside the transaction, same pattern as `CreateClient.php:57`).
- Guard violated → throw `OrganizationReadOnly` with a "already used" message; the shared renderer redirects back with the error. Eligibility is re-checked server-side, so a double-submit is safe.
- One grant per org, lifetime, any cause (expired trial or failed payment). Non-admins never reach it (route gate); the UI hides the button for non-admins via `auth.can.manageBilling`.

## Banner + frontend

- `HandleInertiaRequests` shares the verdict as `auth.access` (alongside the existing `auth.subscription`), typed in `resources/js/types/auth.ts`.
- New React component (working name `TrialBanner`) mounted once in `resources/js/layouts/app/app-sidebar-layout.tsx`, above `{children}` — the single app chrome (post `9de89a6` consolidation).
- Rendered only when `access.readOnly` is true: shadcn `Alert` (warning variant) — "Your trial has ended. Your data is read-only — upgrade to keep managing your organization." with:
  - primary button → `billing.index` (upgrade CTA), wired via Wayfinder route function;
  - secondary "Unlock 24 hours" button for admins while `unlockAvailable` — copy must make the one-time nature explicit.
- If a subscription row exists but is invalid (past-due/canceled), the banner switches to a payment-issue variant. Variant rule: **any subscription row exists → payment-issue copy; no subscription row ever → trial-ended copy.** Both variants live in the lang dictionaries; exact wording at copy time, rule is fixed.
- Form-level disabling of buttons/inputs is explicitly **out of scope**; server redirect + banner carry the UX. (Follow-up polish.)
- EN/FR copy in `lang/en/statuses.php` + `lang/fr/statuses.php` alongside `plan_limit`, plus banner strings in the frontend lang dictionaries (`resources/js/lang/{en,fr}.ts`).

## Caching (transition-aware)

- Verdict cached via `TenantCache` under key `subscription-access` (per org, like `subscription-summary`/`plan-limits`).
- Dynamic TTL: `min(900s (15 min), seconds until next known time transition)` where transitions are `trial_ends_at` (when future) and `access_unlock_until` (when future). A cached "full access" verdict can therefore never outlive the moment it could flip. The 15-minute ceiling bounds staleness for direct-DB edits.
- Event-based transitions invalidate via the existing mechanisms:
  - Stripe events → `StripeWebhookController` → `FlushOrganizationCache` (subscribe, cancel, past-due, retry success).
  - Unlock grant → `GrantAccessUnlock` flushes, like every other write action.
- `TenantCache` gains the ability to accept a computed TTL (current API takes only `TTL_LONG`/`TTL_SHORT` constants) — small signature extension, existing callers unchanged.
- Both consumers (middleware, Inertia shared props) read the same cache entry; there is one truth.
- Note: `subscription-summary` (banner's "trial ends" date display) stays on its existing 1h TTL; the *enforcement* verdict is the new short-TTL entry. `GetPlanLimits` behavior unchanged (starter fallback continues to apply to read-only orgs, though mutations are blocked anyway).

## Testing (Pest)

Conventions: factories + frozen time (`tests/Pest.php`), subscription fakes via direct row creation (`GetPlanLimitsTest` pattern: `subscriptions()->create([...])`, no Stripe API), Stripe doubles via `bindFakeStripeClient()` (`BillingControllerTest`), webhook posts raw JSON to `route('cashier.webhook')` (`CacheInvalidationTest`).

- **Factory states** (`database/factories/OrganizationFactory.php`): add `expired()` (`trial_ends_at => now()->subDay()`) and `unlocked()` (`access_unlock_until` states per test need).
- **Unit — `GetSubscriptionAccessTest`**: on-generic-trial → full access; expired trial, no sub → read-only; expired + consumed unlock (past & future `access_unlock_until`) → unlock not available again; valid sub (each of active / subscription-trial / grace period) → full access; past-due sub (Cashier defaults) → read-only; no trial column, no sub → read-only (trial-less org); TTL-capping: with trial ending in 5 min, cached entry TTL ≤ 300s; unlock running with 10 min left → TTL ≤ 600s; no upcoming transition → TTL = 900s ceiling.
- **Feature — `ReadOnlyAccessTest`**: GET dashboard while read-only → 200; POST create client/user/contract while read-only → redirect back with `readOnly` error, no row created; billing checkout POST while read-only → allowed; unlock POST as admin while read-only → success, org can create again within frozen time +24h; unlock expiry (freeze past `access_unlock_until`) → read-only again; second unlock POST → rejected; unlock POST as non-admin → 403; profile/password POST → allowed while read-only.
- **Middleware/caching feature**: webhook `customer.subscription.updated` (status past-due) rotates cache & next request read-only; retry-success event restores full access.
- Run: `php artisan test --compact` with targeted filters; Pint on dirty files.

## Out of scope

- Pre-expiry "trial ending soon" warnings/emails.
- Per-form button/input disabling (follow-up polish).
- Dunning emails (the unlock doubles as a stopgap).
- Any scheduled job — none needed: time transitions are handled by TTL-capped cache reads, not cron.
