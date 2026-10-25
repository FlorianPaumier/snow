# Important Features Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement this plan task-by-task. This document is a roadmap; external provider selection and explicitly gated features require a focused design before implementation.

**Goal:** Add customer value without requiring workers to report individual stops.

**Architecture:** Keep Laravel Actions for business logic, thin controllers and validated requests, organization-owned models, and Inertia React pages. Reuse existing contracts, payments, mail, queues, tour actions, and UI components. New names below are proposed; existing names were inspected.

**Tech Stack:** PHP 8.5, Laravel 13, Inertia 3, React 19, Wayfinder, Pest 5; existing Cashier/Stripe, Resend and S3 support where appropriate.

**Spec:** User requests in this conversation, 2026-10-16: plan remaining features by priority category; exclude per-stop reporting because it is inapplicable to some services and workers lack time.

## Global constraints

- No per-stop buttons, mandatory photos, GPS check-ins, or assumed completion of individual properties.
- Do not add dependencies without approval. Keep current paid/free entitlements until pricing is deliberately decided.
- Keep organization isolation, authorization, request validation, anonymization and document privacy across public, queue and webhook contexts.
- No production data changes are authorized by this plan. Development fixtures must remain separate from customer records.
- Before executable changes, load relevant Laravel/Wayfinder skills and retrieve version-specific documentation. Boost search-docs was not exposed during this planning session; resolve tool availability at execution.
- Use Artisan generators, project conventions, named backend routes and generated Wayfinder frontend functions. Include EN/FR strings, types, factories, and affected import/export paths.
- Each task: write the named failing Pest tests, implement the smallest behavior, run the targeted tests, then review and commit only intended files. Run Pint on modified PHP and frontend types/build when applicable.
- Verify external API capabilities, region support, costs and terms when the gated integration is selected; vendor marketing is not an API specification.

## Review focus

1. Public and background flows cannot rely on OrganizationScope: scope every tenant-owned lookup explicitly.
2. Repeated clicks, requests and webhook deliveries cannot create duplicate contracts, messages or charges.
3. Issued offers and historical tours retain their snapshot when clients, addresses or prices change.
4. Missing email, revoked links, provider outages and deleted/anonymized clients fail safely.
5. Tour start/finish describes a route only; never infer property completion, billable visits or accurate arrival times.

## Shared implementation checklist

- [ ] Inspect sibling models/actions/controllers/requests/tests; confirm proposed migrations against the actual schema before writing them.
- [ ] Add focused feature tests including cross-organization access, duplicate requests and the failure cases in each task.
- [ ] Implement the task's model/action/controller/page slice; add factories and update affected callers, types, translations, exports and cleanup.
- [ ] Run `php artisan test --compact <affected-test-file>`, `vendor/bin/pint --dirty --format agent`, and, for frontend changes, `bun run test:types` and `bun run build`. Add browser coverage for the primary user interaction.
- [ ] Review the diff and record verified behavior before moving to the next task. Never automatically deploy from this plan.

## Execution order

Quotes → renewals. Route templates may ship independently, then route notifications. Customer payment links remain gated on merchant/provider design.

## 1. Quotes and online acceptance

**Dependency / gate:** Build first; dependency for renewals and quote conversion.

**Files and reuse:** Reuse app/Actions/CreateContract.php, app/Actions/GenerateContractPdf.php and resources/js/pages/contract/components/contract-form.tsx. Add app/Models/Quote.php, app/Actions/SendQuote.php, app/Actions/AcceptQuote.php, app/Http/Controllers/QuoteController.php, app/Http/Controllers/AcceptQuoteController.php, resources/js/pages/quotes/ and tests/Feature/QuotesTest.php. Modify routes/web.php.

**Behavior and interfaces:** Store organization/client/season, immutable issued service and installment snapshots, status, expiry, accepted time and resulting contract ID. Keep drafts separate from contracts so unaccepted offers do not inflate revenue. Send a private, expiring link through queued mail. GET displays; POST explicitly accepts. AcceptQuote::handle(Quote $quote): Contract locks the offer and creates the contract transactionally through CreateContract; enforce one client/season at database level after auditing existing duplicates. Changes to an issued offer require a replacement version and revoke the old link.

- [ ] Write failing tests: Assert draft exclusion from revenue, snapshot stability, expired/replaced links rejected, public tenant isolation, repeated acceptance returns the same contract, concurrent offers cannot create duplicate client-season contracts, missing email leaves a retryable draft.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No signature drawing widget, deposit collection or quote builder redesign. Record explicit acceptance; do not market it as a qualified signature.

## 2. Seasonal renewals

**Dependency / gate:** After quotes; standalone draft-copy slice can ship sooner.

**Files and reuse:** Reuse app/ValueObjects/Season.php, app/Rules/UniqueContractSeason.php and app/Actions/CreateContract.php. Add app/Actions/PrepareSeasonRenewals.php, app/Http/Controllers/SeasonRenewalController.php, resources/js/pages/contract/renewals.tsx and tests/Feature/SeasonRenewalsTest.php.

**Behavior and interfaces:** PrepareSeasonRenewals::handle(User $admin, string $sourceSeason, string $targetSeason, array $contractIds): array creates draft quotes linked to source contracts. Preview selected accounts, explicitly enter revised service amounts, and review target due dates before sending. Copy neither paid flags nor payment methods nor paid dates. Skip anonymized clients and report target-season duplicates. Show draft/sent/accepted/declined/expired counts.

- [ ] Write failing tests: Assert source contracts unchanged, all new installments unpaid, dates explicitly reviewed, same batch rerun creates no duplicate draft, existing target contracts reported, tenants isolated, invalid seasons rejected.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No automatic price increases or automatic mass sending. Office previews and confirms.

## 4. Reusable routes and storm dispatch

**Dependency / gate:** Independent; before customer route notifications.

**Files and reuse:** Reuse app/Actions/CreateTour.php, app/Actions/OptimizeTour.php, app/Http/Controllers/TourController.php and resources/js/pages/tours/. Add app/Models/TourTemplate.php, app/Actions/DispatchTourTemplates.php and tests/Feature/StormDispatchTest.php.

**Behavior and interfaces:** Store named template client IDs/order and default worker/departure/arrival addresses. Dispatch selected templates for a chosen date under a unique batch key; use CreateTour to snapshot current active client details. Preview missing clients/workers and reject invalid templates before dispatch. Preserve historical tour snapshots. Office board shows assigned/not started/in progress/finished from existing timestamps, never property completion counts.

- [ ] Write failing tests: Assert duplicate submission creates one batch, cross-tenant clients/workers rejected, anonymized clients reported, old tour snapshots unchanged, worker sees only assigned tours, finished routes contain no inferred stop statuses.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No new worker interactions, live map, storm weather engine or per-stop progress.

## 5. Customer payment links

**Dependency / gate:** Gated: choose merchant/payment ownership and validate provider support before code.

**Files and reuse:** Reuse app/Models/Payment.php, app/Actions/MarkPaymentPaid.php, app/Actions/SendPaymentReminder.php and existing receipt generation. Add a separate customer-payment controller/webhook, checkout action, reconciliation action and tests/Feature/CustomerPaymentsTest.php. Keep app/Http/Controllers/StripeWebhookController.php subscription handling separate.

**Behavior and interfaces:** Operators must receive their own customer funds through a supported merchant model; current SaaS subscription checkout is not sufficient. After provider selection, persist merchant/account ID and unique checkout/transaction IDs. Create a checkout for the outstanding installment in integer cents. Signed webhook handling verifies merchant, organization, currency, amount and installment, locks reconciliation, updates paid state, and flushes the existing caches. Reconcile pending sessions after provider failures. Define refund/dispute representation before launch; disable manual unmark for provider-settled payments.

- [ ] Write failing tests: Assert forged/wrong-account/wrong-currency/incorrect-amount events cannot mark paid; duplicate and out-of-order events are safe; return URL alone cannot mark paid; concurrent manual and online payments are surfaced for reconciliation; refund/dispute and paid-reminder behavior is correct.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No automatic saved-card charging or custom card form. Hosted checkout first; provider approval is a prerequisite.

## 6. Route-level customer notifications

**Dependency / gate:** After dispatch; email first, SMS only after a provider and consent workflow are chosen.

**Files and reuse:** Reuse app/Actions/StartTour.php, app/Actions/FinishTour.php, app/Mail/ and the existing queue. Add app/Actions/SendTourUpdate.php, a delivery log migration and tests/Feature/TourNotificationsTest.php.

**Behavior and interfaces:** Allow the office to enable route announcements and choose safe wording. Queue one message per recipient/event after commit, with an idempotency key and delivery state. Say route started or route finished, never your property was cleared. Resolve intended recipients within organization, exclude anonymized clients and respect communication preferences. Start with office-triggered email; optional existing start/finish triggers follow.

- [ ] Write failing tests: Assert retries do not duplicate delivery, missing addresses are skipped and reported, rolled-back tour changes send nothing, foreign clients cannot receive updates, opt-outs respected and no individual service completion claim appears.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No ETA promise, homeowner tracking map or extra worker taps.

## Research basis

Priority is a product recommendation, not validated buyer demand. Vendor pages were checked during the preceding comparison on 2026-10-16:

- [Jobber snow removal](https://www.getjobber.com/industries/snow-removal/): quotes, approvals, customer requests, payments and communication.
- [Housecall Pro snow removal](https://www.housecallpro.com/industries/snow-removal-software/): dispatch, customer portal, fleet tracking and reviews.
- [Service Autopilot snow removal](https://www.serviceautopilot.com/snow-removal-software/): reusable routes, flexible billing and accounting sync.

## Completion boundary

This is a planning deliverable. No application changes, migrations, provider accounts, deployments or test-pass claims are included. Before implementation, resolve each explicitly gated design rather than treating every optional idea as approved scope.
