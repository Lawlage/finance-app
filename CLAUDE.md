# Finance App - Claude Code Instructions

## Project Overview

Self-hosted personal finance analyzer. Laravel 13 + React (Inertia.js) + MySQL 8.
Communicates with a separate Python FastAPI AI gateway over LAN.

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3, MySQL 8
- **Frontend:** React 19, TypeScript, Inertia.js v3, Tailwind CSS v4, Recharts
- **Auth:** Laravel Sanctum (cookie-based SPA auth)
- **Queue:** Database driver
- **Containerization:** Docker (app, nginx, mysql)

## Development

All commands run inside Docker via `docker compose exec app <command>`.

- `make up` — start containers
- `make shell` — open container shell
- `make dev` — start Vite dev server (HMR on port 5173)
- `make queue` — start queue worker
- `make migrate` — run migrations
- App available at http://localhost:8080

## API Contract (SPEC.md)

Source of truth: `/home/jacob/finance-project/finance-docs/SPEC.md`

Two endpoints on the AI gateway:
- `POST /categorize` — categorize transactions (X-API-Key header)
- `POST /analyze` — analyze spending data (X-API-Key header)

**Rules:**
- Always match SPEC.md exactly for gateway calls
- Never invent endpoint contracts — flag missing specs
- Ask for updated SPEC.md before writing integration code

## Testing

Four test layers, all enforcing **90% minimum coverage**:

| Layer    | Framework            | Config               | Command                  |
|----------|---------------------|----------------------|--------------------------|
| Backend  | Pest (PHP)          | phpunit.xml          | `make test-backend-cover`|
| Frontend | Vitest + Testing Lib| vitest.config.ts     | `make test-frontend-cover`|
| E2E      | Playwright          | playwright.config.ts | `make test-e2e`          |

- Backend tests: RefreshDatabase trait, MySQL (finance_test DB), array drivers for cache/queue/sessions
- Frontend tests: jsdom, v8 coverage, renderComponent() utility
- E2E: Chromium + Firefox, screenshots/videos/traces on failure
- Full suite: `make ci` (backend + frontend lint + test, no E2E)

## Code Quality

### PHP
- **Pint:** Laravel preset + `declare_strict_types` + `void_return`
- **Larastan:** Level 9 (max strictness)
- **Rector:** PHP 8.3 target, dead code + code quality + type declarations
- All PHP files MUST have `declare(strict_types=1);`

### TypeScript
- **ESLint:** strictTypeChecked + react-hooks plugin
- **Prettier:** Tailwind plugin, single quotes, no semicolons
- **TypeScript:** strict mode

## Conventions

- Conventional commits with scopes: `api`, `frontend`, `docker`, `e2e`, `ci`, `deps`
- Controllers return `Inertia::render()` responses
- AI gateway communication via `App\Services\AiGatewayService`
- All AI gateway calls dispatched as queue jobs — never synchronous
- Idempotent ingestion — no duplicate transactions

## File Structure

```
app/
├── Http/Controllers/     # Inertia controllers
├── Http/Requests/        # Form request validation
├── Http/Middleware/       # HandleInertiaRequests
├── Jobs/                 # Queue jobs (categorize, analyze, upload)
├── Models/               # Eloquent models
├── Services/             # AiGatewayService
└── Exceptions/           # AI gateway exceptions

resources/js/
├── Pages/                # Inertia page components (*.tsx)
├── Components/           # Reusable React components
├── types/                # TypeScript type definitions
└── test/                 # Test setup and utilities

tests/
├── Feature/              # PHP feature tests (Pest)
├── Unit/                 # PHP unit tests (Pest)
└── e2e/                  # Playwright E2E tests
```

## Database

- **Dev:** `finance` database
- **Test:** `finance_test` database
- **Tables:** users, transactions, categories, analysis_runs, jobs, cache, sessions, personal_access_tokens

## Important Notes

- Statement parser logic is NOT implemented — ask user about bank/country first
- `.env` must never be committed
- Uploaded statement files must be deleted immediately after ingestion
- MySQL is internal to Docker network only — never exposed to host
- Always keep `README.md` and `CLAUDE.md` up to date when making changes
