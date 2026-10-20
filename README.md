# CareLink Follow-Up, Laravel 12 vertical slice

This is a small assessment application using **synthetic** patient records only. It is a facility-server module: clinic browsers talk to the Laravel app installed on their local facility server. No browser patient cache or offline browser database is used. National synchronisation is a **design proposal**, not implemented here.

## Requirements
PHP 8.2+, Composer 2, PHP extensions `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, Node.js 20+, npm. Laravel 12, React 18, TypeScript 5, Vite 6 and SQLite. MySQL/PostgreSQL are appropriate in production.

## Start from a clean machine

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the SQLite file: on Git Bash `touch database/database.sqlite`, or in PowerShell `New-Item database/database.sqlite -ItemType File`. Update `DB_DATABASE` in `.env` to the **absolute path** if your Laravel/SQLite setup does not resolve the relative path.

```bash
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Open http://127.0.0.1:8000. For hot reload, also run `npm run dev` in another terminal. Demo credentials are selected in the UI; the API accepts `demo-clinician-0101` and `demo-district` bearer tokens. These are deliberately not production credentials.

## Run all tests

```bash
php artisan test
npm test
```

For load testing, reset and seed 100,000 synthetic patients and **400,000 visits** (takes longer):

```bash
LARGE_SEED=1 php artisan migrate:fresh --seed
```

PowerShell: `$env:LARGE_SEED='1'; php artisan migrate:fresh --seed; Remove-Item Env:LARGE_SEED`. WARNING: `migrate:fresh` deletes the configured demo database tables.

## API

`GET /api/follow-up?facility_id=FAC-0101&status=overdue&overdue_days=7&sort=days_overdue_desc&page=1&page_size=50`

`POST /api/follow-up/p_1/contacted` with body `{ "contacted_at": "2026-10-06T09:00:00Z", "note": "Called", "interaction_id": "<UUID>" }` returns 204. Retry with the **same** interaction ID and exact content for safe replay; a changed request with the same ID returns 409. Contact attempts are append-only. Duplicate concurrent submissions are constrained by a unique index; a concurrent race may surface as a server error, not a finished production-grade replay handler.

### Interpretation of ambiguous requirements

Only the **most recent visit** determines eligibility. The appointment date on that visit must exist. `overdue` means **more than** n whole Lusaka calendar days late. `missed` means 1 through n days late. `due_soon` means today through 7 days into the future. A visit on the appointment date would become the latest visit and supersede the old appointment. Ties on visit date use the larger visit ID. The sample response labels an eight-day-late patient `missed` despite the written default threshold of seven days; this implementation follows the **written strict rule**. Dates come from the `Africa/Lusaka` facility calendar, not the computer clock.

## Trade-offs, security and performance

Filtering, sorting and pagination run in SQL, not PHP or React. An indexed anti-join checks whether a later visit exists. Each screen receives at most 100 rows. This is designed to remain usable for a 10,000-row follow-up list; **measured before-and-after browser timing must still be collected on the candidate's laptop**. Do not claim a performance result before measuring it. Clinical data should be stored on managed encrypted facility servers, accessed using secure sessions and role-based access controls. Replace these static demo tokens with central identity federation, short-lived sessions, audited permission checks, TLS, and secret management before any real deployment.

Accessibility: semantic table and headings; controls with visible labels; a live region for loading/error feedback; keyboard-native buttons/selects. Status uses text as well as colour. I did not implement row virtualization because server pagination bounds the rows. Front-end service tests check URL/token handling and API errors; back-end feature tests cover statuses, date boundaries, latest visit, permissions, validation, paging and idempotency.

## Deliberately outside this slice

Full login, national sync, robust outage retry queues, reconciliation, laboratory FHIR integration, reporting integration, structured logs beyond API metadata, deployment orchestration, infrastructure monitoring and full 100k-dataset performance measurement. See Sections A and D for the proposed production approach. Demonstrate and explain code before submitting, and document all AI assistance truthfully in `declaration.md`.
