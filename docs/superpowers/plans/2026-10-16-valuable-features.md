# Valuable Features Implementation Plan

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

Implement only when the stated dependency and buyer need are satisfied; these are separate releases, not one large project.

## 7. Public quote requests

**Dependency / gate:** After important #1 for conversion; capture can ship independently.

**Files and reuse:** Reuse app/Actions/SubmitContactInquiry.php only as a validation/mail pattern, not as the tenant lead store. Add app/Models/ServiceRequest.php, app/Actions/SubmitServiceRequest.php, app/Http/Controllers/ServiceRequestController.php, resources/js/pages/service-requests/ and tests/Feature/ServiceRequestsTest.php.

**Behavior and interfaces:** Add a revocable organization-specific public link. Collect contact details, service address and selected services; validate and throttle. Office inbox supports new/contacted/quoted/closed. Office converts an approved request into a client and draft quote; do not create client records directly from anonymous submissions.

- [ ] Write failing tests: Test spam throttling, invalid input, tenant assignment derived from link, repeat conversion safety, manual matching of existing clients and anonymization/retention cleanup.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No public automatic pricing or service-area engine.

## 8. Multiple properties and property instructions

**Dependency / gate:** Independent; complete before property-dependent billing.

**Files and reuse:** Modify app/Models/Client.php, app/Models/Contract.php, app/Actions/CreateTour.php, app/Actions/AnonymizeClientTourStops.php, app/Rules/UniqueContractSeason.php, client/contract forms and CSV import/export code. Add app/Models/Property.php and tests/Feature/PropertiesTest.php.

**Behavior and interfaces:** First ship structured instructions against the existing service address. Then introduce organization-owned properties attached to clients, preserving billing address separately. Backfill one property from each existing service address; link existing contracts to it. Adopt one contract per property/season with a database constraint. Update every client/season uniqueness caller and import format. Snapshot property IDs and instructions in newly created tours without rewriting historical stops.

- [ ] Write failing tests: Test legacy client/contract migration, two properties for one client, property-season duplicates, billing address separation, historical tours, tenant isolation, client anonymization/deletion and old CSV compatibility.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No site-map drawing tool. Property identity migration must precede multi-property UI.

## 9. Customer portal

**Dependency / gate:** After quotes; add payment links only when important #5 ships.

**Files and reuse:** Reuse contract document and receipt actions and accepted quote snapshots. Add app/Http/Controllers/CustomerPortalController.php, scoped portal access action, resources/js/pages/customer-portal/ and tests/Feature/CustomerPortalTest.php.

**Behavior and interfaces:** Email an expiring, revocable private link to a single client account. Show accepted contracts, installment balances and authorized document downloads. Add quote acceptance/payment links through already shipped flows. Recheck tenant/client ownership on every resource download and exclude internal notes. No service history because per-property records are excluded.

- [ ] Write failing tests: Test expired/revoked links, rate-limited link requests, generic responses preventing email enumeration, unrelated document IDs, anonymized clients, shared billing contacts and no staff-only notes.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No separate homeowner password system, messaging inbox or fabricated visit history.

## 10. Per-push and extra-service billing

**Dependency / gate:** After properties; gated on an office workflow that reliably confirms billable service.

**Files and reuse:** Reuse app/Models/ContractService.php, app/Models/Payment.php and receipt/export actions. Add app/Models/ServiceCharge.php, app/Actions/ConfirmServiceCharges.php, office charge review page and tests/Feature/ServiceChargesTest.php.

**Behavior and interfaces:** Keep seasonal contracts unchanged. Office selects applicable properties/services after a storm, reviews quantities/rates/exclusions and explicitly confirms charges. Tour finish alone never creates a charge. Use unique source-event/property/service keys and immutable price snapshots. Keep variable charges separate from fixed PaymentSlot installments; design shared balance/export/payment-link treatment before releasing charges.

- [ ] Write failing tests: Test excluded and unserviced properties, duplicate confirmation, corrected drafts, immutable issued amounts, tenant boundaries, seasonal accounts not double-billed, totals and export reconciliation.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No worker stop confirmation, automatic charge from a tour or per-inch rules. If office confirmation is impractical, defer this feature.

## 11. Accounting integration

**Dependency / gate:** Gated on buyer-selected accounting package and a verified API mapping.

**Files and reuse:** Reuse app/Actions/ExportSeasonPayments.php, organization settings and queue patterns. Add provider-specific credential storage, export action/job, sync log and tests/Feature/AccountingSyncTest.php only after choosing the provider.

**Behavior and interfaces:** Start with a provider-compatible CSV mapping using existing export data. Add one-way sync only if CSV fails buyer needs. Define customer/invoice/payment/tax mappings; current installment records must not be represented as a full accounting ledger. Encrypt credentials, isolate organizations, record remote IDs, retry idempotently and expose failed records for office resolution.

- [ ] Write failing tests: Test totals/currency/tax mappings, export escaping, duplicate retries, expired credentials, remote failures, tenant isolation and reconciliation against local source records.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No two-way sync or multiple accounting providers in the first version.

## 12. Offline route access

**Dependency / gate:** Independent after route schema stabilizes; offline updates are unnecessary unless existing start/finish needs them.

**Files and reuse:** Modify resources/js/pages/tours/worker.tsx and shared tour components; add a narrowly scoped service worker/cache strategy, sync endpoint if needed, and tests/Browser/OfflineToursTest.php.

**Behavior and interfaces:** Cache only the authenticated worker route essentials. Show freshness and offline state; purge cached personal data on logout, account/organization change and expiry. Phase one is read-only route access. If required, queue only existing tour start/finish with client operation IDs and visible pending/conflict status; server rechecks assignment and lifecycle on reconnect.

- [ ] Write failing tests: Browser tests cover airplane mode after initial load, refresh offline, stale assignment, replay, logout/account switch cache purge, unavailable first load and conflicting start/finish.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No offline stop reports, broad authenticated response caching or native app.

## Research basis

Priority is a product recommendation, not validated buyer demand. Vendor pages were checked during the preceding comparison on 2026-10-16:

- [Jobber snow removal](https://www.getjobber.com/industries/snow-removal/): quotes, approvals, customer requests, payments and communication.
- [Housecall Pro snow removal](https://www.housecallpro.com/industries/snow-removal-software/): dispatch, customer portal, fleet tracking and reviews.
- [Service Autopilot snow removal](https://www.serviceautopilot.com/snow-removal-software/): reusable routes, flexible billing and accounting sync.

## Completion boundary

This is a planning deliverable. No application changes, migrations, provider accounts, deployments or test-pass claims are included. Before implementation, resolve each explicitly gated design rather than treating every optional idea as approved scope.
