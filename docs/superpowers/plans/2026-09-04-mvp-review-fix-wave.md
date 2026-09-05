# Plan: Post-MVP-Review Fix Wave

Fix all code-level issues from the 5-domain MVP launch review of `ad10f2b..6c03a53`.
**Excluded by user instruction:** anything that is purely a platform env-variable update (production mailer values, Stripe secret values, cache store choice, Gotenberg URL values) — those are configured directly in the hosting platform. `.env.example` entries and config defaults remain in scope where they make the app correct regardless of platform values (e.g., `APP_TIMEZONE`).

## Global Constraints

- Laravel 13, Pest 5, Inertia v3 + React 19, TypeScript strict. PHP 8.5, `declare(strict_types=1)` everywhere.
- **Every change must be programmatically tested.** Run `vendor/bin/pint --dirty --format agent` after any PHP edit. Tests via `php artisan test --compact --filter=...` (minimum needed).
- 100% coverage gate: `app/` fully covered — every new code path needs a test.
- Use existing patterns/conventions from sibling files. Actions live in `app/Actions` with a single `handle()` method. Form Requests for validation. No superfluous PHP annotations.
- Frontend: Wayfinder route functions from `@/actions`/`@/routes` (never hardcoded URLs in handwritten code); EN/FR lang files must stay key-p
arallel (`resources/js/lang/en.ts` is typed against `fr.ts` structure); translation keys used must exist in both.
- No new dependencies without approval. No doc files unless requested.
- Do not delete tests without approval.

## Tasks

### Task 1: Fix unverified-user redirect loop + strip dead login-bomb vector

`routes/web.php:54` — change `verified:login-challenge.show` to `verified:verification.notice` so an authenticated-but-unverified user is redirected to the verify-email notice (an auth route, not guest) instead of bouncing into an infinite loop with `RedirectIfAuthenticated`. Add a regression feature test: authenticated user with `email_verified_at = null` hitting `GET /dashboard` gets a redirect to the verify-email route (not a loop / not to login-challenge). Check `verification.notice` is auth-gated (routes/web.php:223 area).

### Task 2: Stop unbounded login-code mail-bombing

`app/Http/Requests/CreateSessionRequest.php:45` — remove the `RateLimiter::clear($this->throttleKey())` on credential success; the limiter should clear only when the challenge is verified (`LoginChallengeController` already clears it there on success). Update the existing test that asserts the clear-on-credential-success behavior (tests/Feature/Http/Controllers/SessionControllerTest or similar — locate it) to assert instead that credential success does NOT clear the limiter (a subsequent 4th/5th attempt within the window still throttles). Full flow: login → code → verify → limiter cleared.

### Task 3: Make Stripe redirect URLs absolute

`app/Http/Controllers/BillingController.php:54-55,73-74` — remove `absolute: false` at all three call sites so `success_url`/`cancel_url`/portal `return_url` are absolute URLs (Stripe requirement). Update `tests/Feature/BillingControllerTest.php`'s `bindFakeStripeClient` assertions to assert the outgoing payload contains the absolute URL (contains `http`, not a bare `/settings/billing`). ~6 assertion updates.

### Task 4: Timezone-correct scheduled emails

`config/app.php:70` — `'timezone' => env('APP_TIMEZONE', 'America/Toronto')`; add `APP_TIMEZONE=America/Toronto` to `.env.example` with a comment. No test required beyond suite passing (config default change) — but add a tiny unit test asserting the fallback default is `America/Toronto` when env unset? Only if cheap and follows existing config tests pattern; skip if none exist.

### Task 5: Plan-cap and status feedback in the frontend

Two sub-fixes in one task (same shape, same pages):
(a) **Plan-cap errors**: `bootstrap/app.php:38-44` renders `PlanLimitExceeded`/`PlanFeatureLocked`/`OrganizationOwnerProtected` as `back()->withErrors(['planLimit' => ...])` etc. — add a bag-level error alert on: client create page, user-management create/edit pages, contract show page (reminder send), and anywhere else these exceptions can fire (grep backend for `PlanLimitExceeded|PlanFeatureLocked|OrganizationOwnerProtected` throws, and map to their landing pages). Use a shared alert component if one exists (look for an Alert/Callout component in resources/js/components/ui). Wire `errors.planLimit`, `errors.featureLocked`, `errors.ownerProtected`.
(b) **Success/status flash**: `app/Http/Middleware/HandleInertiaRequests.php` — add `'status' => fn () => $request->session()->get('status')` to shared props so pages already reading `usePage().props.status` (clients/index, clients/show, contract/index, contract/show, user-management/index) display "Client created", "PDF is being generated", etc.
Tests: update/extend tests asserting `session('status')` for Inertia requests to instead assert the shared prop is present on the destination page. Add a browser-level test if the visual group pattern fits, else feature tests asserting shared prop.

### Task 5 is the largest UX task. Plan gives implementer freedom on exact component choice but requires: all three error keys wired on all landing pages, status prop shared and rendered on all pages that read it, EN/FR keys added to both lang files if any new user-visible strings are introduced.

### Task 6: Confirmation dialogs for destructive actions

Wrap delete actions in confirmation dialogs using the existing Dialog pattern (from anonymize client / delete-user components) and existing but unused translation keys (`t.clients.deleteConfirmTitle/Description`, `t.users.deleteConfirm*`, and add any missing FR/EN keys — the keys already exist per review). Sites: contract show (delete contract button), contract index (inline delete form), clients index (delete), clients show (delete), user-management index (delete), organization edit (ownership transfer). Also verify the service-row error surfacing fix (contract-form.tsx:307 reads only `errors['services.0.label']` — surface errors on any row, not just row 0).

### Task 7: Marketing copy + ToS truthfulness

(a) Pricing page "Email invoices & receipts" → "Printable/downloadable invoices & receipts" (and matching feature/home/data.ts claims — verify each against app reality; data.ts:80-81 requires every claim be verified against the app). (b) ToS: fix the "read-only view" paragraph (terms.tsx:49-55) to describe the shipped free-caps model (data stays available; writes continue under Free plan caps). (c) PlanBanner "View Pro" link 403s for non-admin workers — render the billing link only when `auth.can?.manageBilling` is available in shared props (check `HandleInertiaRequests` shares `auth` with `can` — verify and use it), else hide or point to a neutral page.
Browser test `MarketingClaimsTest` may assert on these strings — update accordingly.

### Task 8: Domain data-integrity fixes (batched)

Batch of small same-shape fixes, one dispatch:
(a) `app/Actions/AnonymizeClient.php` — also delete payment-receipt PDFs (`documents/payments/{id}/payment-receipt.pdf` via `GeneratePaymentReceiptPdf::pdfPath($payment->id)`) for every payment of each contract; keep contract PDF deletion as-is. Test mirroring AnonymizeClientTest's contract-PDF deletion test for receipts.
(b) `app/Actions/UpdateContract.php`, `app/Actions/UpdateClient.php` — delete the stored contract PDF after update (client update affects all their contracts); `app/Actions/UnmarkPaymentPaid.php` — delete the stored receipt PDF. Tests for each.
(c) `app/Actions/ExportSeasonPayments.php:54-58` — replace `cursor()` with `lazyById()` (keeps `with()`). Test: existing export tests must still pass; behavior unchanged.
(d) `app/Console/Commands/AnonymizeExpiredClientsCommand.php` — wrap per-client loop body in try/catch, log `Log::error`/`$this->error` with client id, continue, and report failure count in summary. Test: a failing client doesn't stop the sweep (inject a mock AnonymizeClient that throws for one client, assert others still processed).
(d2) `app/Actions/GetUnpaidInstallments.php:74-81` — use `Client::displayName()` (or reuse the model's anonymized branch) so anonymized clients with unpaid installments don't render empty strings. Test asserting non-empty display for anonymized client.
(d3) Zero-amount installments: filter `amount > 0` in GetUnpaidInstallments if that matches product intent (reviewer noted $0 rows pollute the unpaid list) — check `CreateContract` always creates all 4 slots; decide: filter zero amounts from the unpaid list. Test.
(e) PDF jobs `failed()` handlers: add `failed()` logging to `GenerateContractDocumentPdf` and `GeneratePaymentReceiptDocumentPdf` (mirror `SendContactInquiry`'s pattern). Tests if cheap (queue fake failure).
(e2) `app/Http/Controllers/UserInvitationController.php:23` — restrict the email→org lookup on the accept-invitation page to pending invitations (`whereNull('invitation_accepted_at')`) to stop email/organization enumeration. Test: unauthenticated request with a registered-but-not-pending email gets no org name; pending invitee gets it.
(e3) Client-number race: `app/Actions/CreateClient.php` — catch unique-constraint `QueryException` and recompute the number once or twice inside the transaction (retry loop, max 3 attempts), else rethrow. Test: simulate race by pre-seeding the sequence conflict (e.g., insert a row with the next number inside the test, then call CreateClient, assert success with incremented number).

### Task 9: Hardening batch (registration throttle, 2FA off, model guarding, plan-banner link)

(a) `routes/web.php` register POST — add `throttle:5,1` middleware.
(a2) `app/Http/Requests/CreateSessionRequest.php` — also address enumeration: keep as is; only Task 2 change.
(b) `config/fortify.php:154-158` — remove `Features::twoFactorAuthentication(...)` (dead feature, silently bypassed by custom challenge login). Verify no tests depend on 2FA routes (`grep -r "two-factor" tests/`); remove/update accordingly (with controller-approval caveat — do NOT delete tests, update them if they reference it).
(b2) Check `resources/js` for 2FA references after removal (settings pages may link to two-factor endpoints — check user-profile/settings pages); clean up dead frontend references if trivially safe (any non-trivial restructure → report as concern, don't guess).
(b3) `config/essentials.php:8` — flip `Unguard::class => false` (vendor default). CRITICAL PREREQUISITE: no model defines $fillable/$guarded today — this will break mass assignment app-wide. MUST add explicit `$guarded`/`$fillable` to every model first: User, Organization, Client, Contract, UnguardedFixOrder.md ContractService, Payment, ContactInquiry. This is the risky one — plan a detailed subtask; consider doing this last and running the FULL suite after. Also `database/factories/*` may rely on unguarded — verify factories work with guarded models (Laravel factories bypass guarding by default? No — factories respect fillable via `make()`? Actually Laravel factories DO respect fillable/guarded for `create()/make()` unless the model is unguarded; verify with a quick factory smoke test before full flip).
(b4) README production checklist: add mailer item. Also remove the GitHub Actions badge (README.md:12) and fix the "150 tests" claim (README.md:34) — the user requested fixes, and README is user-facing documentation the review flagged; updating it is in scope (not a new doc file).
(b5) `composer.json` scripts `test:unit` — add `--exclude-group=visual` to align with CI; the macOS-only visual baselines break Linux runs. Check `composer test` rector dry-run debt — do NOT fix 36-file rector debt here (out of scope); note for final review. Actually: `composer test` runs rector --dry-run which fails on known debt — out of scope for this plan; note in ledger as deferred.
(b6) Deploy job image pin: `.gitlab-ci.yml:202` `curlimages/curl:latest` → pin to a specific version (e.g. `curlimages/curl:8.16.0` or a recent stable pin — pick the latest stable from Docker Hub at time of writing, verify tag exists).

### Task 10: CSV export + client-number race [moved into Task 8 batch items c/e3]

### Task 10 (final): Webhook signature tests

`tests/Feature/CacheInvalidationTest.php` — add signature-verified webhook tests: set `config(['cashier.webhook.secret' => 'whsec_test'])` in-test, compute a valid Stripe signature header for a payload (Cashier's `Webhook::constructEvent` / manual HMAC computation matching Cashier's VerifyWebhookSignature middleware expectations), post good-signature event → 200 + cache flush; bad signature → 400. Also add the production-guard behavior test only if a guard is implemented in Task 5 area — skip guard implementation, only tests for existing behavior with secret set.

## Task Dependencies

All tasks are independent and can be dispatched sequentially (they touch different files) except: Task 5 and 7 both touch frontend lang files and pages; Task 9(b3) guards+unguard flip affects all models (run last); Task 8 and 9 both touch app/Actions — different actions though. No blocking dependencies; dispatch order: 1→2→3→4→5→6→7→8→9→10.

## Verification

Full suite `php artisan test --compact` (excluding nothing — the CI-equivalent run) green, coverage 100% maintained, `vendor/bin/pint --dirty` clean, `php artisan test --compact --filter=...` focused runs during development. Frontend: `bun run build` (or `npm run build`) must succeed for lang/type changes. `php artisan route:list` sanity check for route changes. Also `php artisan schedule:list` to verify timezone changes work.
</content>
