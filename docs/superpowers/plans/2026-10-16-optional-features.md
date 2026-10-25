# Optional Features Implementation Plan

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

## 13. Reviews and referrals

**Dependency / gate:** After communications; office controls invitations.

**Files and reuse:** Reuse mail/queue patterns; add organization review URL settings, app/Actions/SendReviewRequest.php and tests/Feature/ReviewRequestsTest.php.

**Behavior and interfaces:** Office selects customers after a season; preview and send one invitation with a delivery key. Include an optional referral link to the shipped quote request form. Track referral attribution only when the request contains the valid reference.

- [ ] Write failing tests: Test tenant-safe URLs/references, repeated sends, missing email, preferences, anonymization and no inferred service completion.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No rewards, review gating or automatic completion-triggered invitations.

## 14. Route profitability

**Dependency / gate:** Gated on meaningful cost inputs; never claim per-property margin from route timestamps.

**Files and reuse:** Reuse app/Actions/GetSeasonSummary.php and existing tour timestamps. Add app/Actions/GetRouteCostEstimate.php, office cost form and tests/Feature/RouteCostEstimateTest.php.

**Behavior and interfaces:** Start with estimated route costs using office-entered crew hours, hourly cost, fuel/material totals and explicit assumptions. Label scheduled revenue allocation separately from recognized revenue. Keep whole-route elapsed time distinct from labor hours; support several workers and breaks without guessing.

- [ ] Write failing tests: Test integer money, zero/missing inputs, multi-worker hours, cost totals, estimate labels, no inferred property duration and tenant boundaries.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No automatic per-property profit ranking or payroll.

## 15. Live fleet location and ETAs

**Dependency / gate:** Gated: actual multi-crew need, location source, privacy/retention rules and additional vendor costs.

**Files and reuse:** Extend tours and organization settings only after a supported vehicle/device provider is selected; add ingestion action, latest-location view and tests/Feature/FleetLocationTest.php.

**Behavior and interfaces:** Read vehicle-provider location into an office-only map with observation time and stale status. Match vehicles to tours explicitly. Add customer ETA only after measuring accuracy; route start alone cannot support an ETA.

- [ ] Write failing tests: Test wrong tenant/vehicle, stale or reordered samples, provider outage, unauthorized location access, revoked tracking and retention cleanup.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No worker location taps, continuous browser tracking or promised arrival times in first release.

## 16. Weather alerts

**Dependency / gate:** Gated: choose provider and validate coverage for customer service areas.

**Files and reuse:** Add organization weather settings, app/Actions/CheckSnowForecast.php, scheduled command, mail and tests/Feature/WeatherAlertsTest.php; later link to shipped route dispatch.

**Behavior and interfaces:** Office chooses a service area and threshold; scheduled checks send a deduplicated advisory with forecast time/source. Link to route preview. Human approval dispatches routes. Keep weather service independent of tour lifecycle.

- [ ] Write failing tests: Test forecast units, area/timezone, stale data, threshold transitions, repeated checks, provider failure and tenant isolation.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No autonomous dispatch, guaranteed snowfall measurement or weather-triggered charges.

## 17. Subcontractor assignments

**Dependency / gate:** Gated on customers actually using external crews.

**Files and reuse:** Reuse worker assignment and invitation patterns only where privileges fit. Add organization-owned subcontractor assignments and tests/Feature/SubcontractorAccessTest.php.

**Behavior and interfaces:** First support office-maintained external crew contact and assigned route, with a scoped read-only route link if needed. Hide client pricing and unrelated accounts. Access expires and is revocable; track acknowledgment only if customer operations require it.

- [ ] Write failing tests: Test expired links, tenant isolation, reassignment, price redaction, revoked access, anonymized contacts and existing worker authorization unchanged.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No contractor marketplace, payroll, compliance management or payouts.

## 18. Equipment maintenance

**Dependency / gate:** Gated on repeated fleet-management demand.

**Files and reuse:** Add app/Models/Equipment.php, app/Actions/RecordEquipmentMaintenance.php, a simple settings page and tests/Feature/EquipmentMaintenanceTest.php.

**Behavior and interfaces:** Office tracks equipment name, status, maintenance due date and service history. Add overdue email through existing scheduler/mail. Keep optional route assignment as a later independent slice.

- [ ] Write failing tests: Test due dates/timezones, reminder deduplication, tenant access, maintenance history and deactivation.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No telemetry, parts inventory, fuel integration or predictive maintenance.

## 19. AI drafting assistant

**Dependency / gate:** Last; gated on a specific repetitive office task.

**Files and reuse:** Reuse quote drafts from important #1. Add one isolated draft suggestion action, office preview and tests/Feature/QuoteDraftSuggestionTest.php after selecting a provider.

**Behavior and interfaces:** Suggest editable quote wording from approved service labels and office instructions. Treat model output as untrusted text. Human review is required; deterministic existing code controls prices, taxes, recipient and acceptance. Minimize customer data and define retention/budget limits before integration.

- [ ] Write failing tests: Use fake provider responses to test timeout, malformed output, injected instructions, tenant leakage, escaping, budget enforcement and no automated send or monetary mutation.
- [ ] Implement the behavior above following the shared checklist; include migration/factory and existing caller updates where required.
- [ ] Run the named test file above, affected existing regressions, and applicable shared checks; review before release.

**Excluded from this release:** No autonomous pricing, contract terms, customer promises or actions.

## Research basis

Priority is a product recommendation, not validated buyer demand. Vendor pages were checked during the preceding comparison on 2026-10-16:

- [Jobber snow removal](https://www.getjobber.com/industries/snow-removal/): quotes, approvals, customer requests, payments and communication.
- [Housecall Pro snow removal](https://www.housecallpro.com/industries/snow-removal-software/): dispatch, customer portal, fleet tracking and reviews.
- [Service Autopilot snow removal](https://www.serviceautopilot.com/snow-removal-software/): reusable routes, flexible billing and accounting sync.

## Completion boundary

This is a planning deliverable. No application changes, migrations, provider accounts, deployments or test-pass claims are included. Before implementation, resolve each explicitly gated design rather than treating every optional idea as approved scope.
