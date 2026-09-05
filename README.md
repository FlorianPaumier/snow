# Frostline OPS

Frostline OPS is the back office for a snow-removal business: a bilingual (English/French) SaaS for managing a client directory, seasonal contracts with itemized service lines, and payment tracking — deposit, installments, balance — with dashboards, receipts, and automated reminders on top. It deliberately does not try to run trucks or crews.

Built on the [Laravel Starter Kit (Inertia & React)](https://github.com/nunomaduro/laravel-starter-kit-inertia-react) — strict, type-safe Laravel with an Actions-oriented architecture and 100% coverage/type gates.

## Features

- **Multi-tenant organizations** — each organization's data is isolated via a global scope; roles are admin, worker, and user, with abilities like `manage-billing`, `manage-users`, `manage-clients`, `manage-contracts`, and `manage-organization`.
- **Clients & contracts** — individual and company clients; one contract per client per season (seasons run July → June, e.g. `2026-2027`) made of itemized service lines.
- **Payment tracking** — installments tracked slot by slot (deposit, first and second installments, balance) and marked paid with method (cash, debit, virement, cheque) and date.
- **Documents** — contract PDFs and payment receipts rendered via [Gotenberg](https://gotenberg.dev) on the queue, printable and downloadable.
- **CSV export** — hand the season to your accountant in one click: every installment with client, contract, amount, method, and paid date.
- **Dashboard** — revenue evolution, client growth, and season summary charts.
- **Automated reminders** — scheduled installment reminders (with anti-spam caps) and a weekly payment digest email.
- **Subscriptions** — Free plan (1 user, 15 clients) vs. Pro (unlimited, Stripe subscription via Cashier with annual CAD pricing — one payment covers the season).
- **Privacy** — Quebec Law 25 compliance: nightly anonymization/erasure of clients whose data has passed the retention window (`CLIENT_RETENTION_YEARS`, default 6 years).
- **Auth** — Fortify with login challenge codes by email, user invitations, password resets, email verification, and organization ownership transfer.
- **Bilingual** — English and French, switchable per user (interface, validation messages, and emails).

## Tech Stack

- PHP 8.5 / Laravel 13 / Inertia v3 / React 19 / TypeScript / Tailwind CSS 4
- Laravel Wayfinder (typed frontend route functions), Fortify (auth), Cashier (Stripe), Essentials
- Pest 5 (Feature / Browser / Unit tests), Larastan (PHPStan level max), Rector, Pint, oxlint/oxfmt
- Bun for JS dependencies and the Vite dev pipeline

## Requirements

- PHP 8.5+ with a code coverage driver (e.g. [Xdebug](https://xdebug.org/docs/install)) for the coverage gate
- [Bun](https://bun.sh)
- [Composer](https://getcomposer.org)
- Optional: Docker — Postgres, Redis, and Gotenberg are provided by `docker-compose.yml`

## Getting Started

```bash
# Install dependencies and set up the project (.env, key, migrations, JS build)
composer setup

# Start the dev server, queue worker, scheduler, log tail, and Vite
composer dev
```

The default configuration uses SQLite with database-backed sessions, cache, and queue — no external services needed to boot. Gotenberg-backed PDF generation requires a reachable `GOTENBERG_URL` (see below).

### Optional: Infrastructure via Docker

`docker-compose.yml` provides infrastructure only — the app itself runs on the host and connects over the published ports:

```bash
docker compose up -d   # Postgres (127.0.0.1:5432), Redis (127.0.0.1:6379), Gotenberg (127.0.0.1:3000)
```

Point `.env` at these services (`DB_CONNECTION=pgsql`, `CACHE_STORE=redis`, `GOTENBERG_URL=http://localhost:3000`, …) if you want the full local stack.

### Optional: Browser Testing Setup

Pest browser tests use Playwright:

```bash
bun add playwright
bunx playwright install
```

### Verify Installation

```bash
composer test
```

You should see 100% test coverage and all quality checks passing.

## Available Tooling

### Development
- `composer dev` — Laravel server, queue worker, scheduler, Pail log tail, and Vite dev server concurrently

### Code Quality
- `composer lint` — Rector (refactoring), Pint (PHP formatting), and oxfmt (JS/TS formatting)
- `composer test:lint` — Dry-run mode for CI/CD pipelines

### Testing
- `composer test:type-coverage` — Ensures 100% type coverage with Pest
- `composer test:types` — Larastan/PHPStan at maximum strictness, plus `tsc --noEmit`
- `composer test:unit` — Pest tests with 100% code coverage requirement
- `composer test` — Complete test suite (lint, type coverage, types, unit)

### Maintenance
- `composer update:requirements` — Updates all PHP and Bun dependencies to latest versions

## Scheduled Work

| Command | Schedule | Purpose |
| --- | --- | --- |
| `payments:send-reminders` | Weekdays 09:00 | Installment reminders (Pro plan; anti-spam caps: week window, weekly cooldown, max 3 per installment) |
| `payments:send-digest` | Mondays 08:00 | Weekly payment recap per organization |
| `clients:anonymize-expired` | Daily 03:00 | Law 25 retention: anonymize (or erase contractless) clients past the retention window |

## Production Checklist

- [ ] **Queue worker** — run `php artisan queue:work` under a process supervisor (e.g. Supervisor). Queued work includes PDF generation, login codes, invitations, payment reminders, and weekly digests.
- [ ] **Scheduler** — add the cron entry `* * * * * php /path/to/artisan schedule:run` (or run `php artisan schedule:work` under a supervisor) for the scheduled commands above.
- [ ] **Gotenberg** — a reachable Gotenberg service configured via `GOTENBERG_URL`, used to render contract PDFs and payment receipts.
- [ ] **Stripe webhook** — an endpoint pointing to `POST /stripe/webhook`, signed with `STRIPE_WEBHOOK_SECRET`.
- [ ] **Stripe prices** — `STRIPE_PRICE_PRO_ANNUAL` must reference the Pro plan's annual CAD, tax-exclusive price (Stripe Tax adds GST/QST at checkout).

## CI/CD

GitLab CI (`.gitlab-ci.yml`) splits the `composer test` gate across jobs — lint, rector (dry-run), types (Pest type coverage + `tsc`), phpstan, and the full Pest suite including browser tests (TIA, excluding the `visual` group whose PNG baselines are platform-specific). Tests run against in-memory SQLite, so no database service is needed.

The CI toolchain image (`$CI_REGISTRY_IMAGE/ci:latest`) is built and pushed **by hand** — see the procedure in `Dockerfile.ci`. Deploys trigger Laravel Cloud via its deploy hook on the default branch.

## License

The underlying Laravel Starter Kit Inertia React was created by [Nuno Maduro](https://x.com/enunomaduro) under the [MIT license](https://opensource.org/licenses/MIT).
