# Finance App - Claude Code Instructions

## Project Overview

Self-hosted personal finance analyzer. Laravel 13 + React (Inertia.js) + MySQL 8.
The app **exposes its own MCP server** (`laravel/mcp`) so the user's own Claude client
(Claude Desktop / Code) reads PII-sanitized transaction data and writes back categories
and spending analyses. There is no locally-hosted AI and no LLM API key in the app.

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

## MCP Server

The app publishes a `FinanceServer` MCP server (`app/Mcp/`), registered in `routes/ai.php`:

- **Transport:** local only — `Mcp::web('/mcp/finance', ...)` over the LAN behind
  `auth:sanctum` (Bearer personal access token) + `throttle:mcp`, plus `Mcp::local('finance')`
  for stdio / `php artisan mcp:inspector finance`. No OAuth, no public exposure.
- **Resources (read):** `finance://transactions`, `finance://spending-summary`,
  `finance://categories`, `finance://category-rules`, `finance://analyses`.
- **Tools (write):** `get_transactions`, `list_uncategorized`, `set_category`,
  `bulk_set_category`, `record_analysis`.
- **Prompt:** `analyze_spending`.

**Rules:**
- Every transaction value exposed over MCP MUST pass through `App\Services\TransactionSanitizer`.
  Only `id, date, amount, account (label), category, sanitized description` may leave the box —
  **never `raw_text`**.
- The `replacement_rules`, `settings`, and `mcp_access_logs` tables are never MCP primitives.
- Every MCP response is logged to `mcp_access_logs` (egress audit, surfaced on the Privacy page).
- `spending-summary` aggregates must avoid double-counting internal flows: exclude the
  `Transfer` category (own-account moves, incl. bill-funding transfers) **and** loan-account
  ledger lines that mirror cash flows. Use the `Transaction::excludingTransfers()` and
  `excludingLoanAccounts()` scopes; loan accounts are listed in `Transaction::LOAN_ACCOUNTS`.

## PII Sanitization

`TransactionSanitizer` runs two passes before MCP egress:
1. **Replacement map** (`replacement_rules`, encrypted at rest) — literal account numbers /
   names → user-defined friendly labels (e.g. "Joint Savings").
2. **Regex fallback** for unmapped NZ patterns (account numbers, masked cards, emails, phones,
   personal-name initials). The replacement style is the `fallback_mode` setting:
   `pseudonym` (stable HMAC tags like `Person-2B`, default) or `redact` (`[NAME]`, `[ACCOUNT]`).

Manage the map, mode, and audit log on the **Privacy & MCP** page (`/privacy`).

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
- MCP primitives live in `app/Mcp/` and all reads flow through `TransactionSanitizer`
- `CategorizeTransactions` is local keyword-rule matching only (no AI); unmatched
  transactions are categorized by the Claude client over MCP
- Idempotent ingestion — no duplicate transactions

## File Structure

```
app/
├── Http/Controllers/     # Inertia controllers (incl. PrivacyController)
├── Http/Requests/        # Form request validation
├── Http/Middleware/      # HandleInertiaRequests
├── Jobs/                 # Queue jobs (rules-only categorize, upload)
├── Mcp/                  # MCP server, Resources, Tools, Prompts, Concerns
├── Models/               # Eloquent models
└── Services/             # TransactionSanitizer, WestpacCsvParser

resources/js/
├── Pages/                # Inertia page components (*.tsx, incl. Privacy.tsx)
├── Components/           # Reusable React components
├── types/                # TypeScript type definitions
└── test/                 # Test setup and utilities

tests/
├── Feature/              # PHP feature tests (Pest, incl. Mcp/)
├── Unit/                 # PHP unit tests (Pest)
└── e2e/                  # Playwright E2E tests
```

## Database

- **Dev:** `finance` database
- **Test:** `finance_test` database
- **Tables:** users, transactions, categories, category_rules, analysis_runs, imports,
  job_statuses, replacement_rules, settings, mcp_access_logs, jobs, cache, sessions,
  personal_access_tokens

## Important Notes

- Statement parser logic targets Westpac NZ CSV (`WestpacCsvParser`)
- `.env` must never be committed
- Uploaded statement files must be deleted immediately after ingestion
- MySQL is internal to Docker network only — never exposed to host
- The MCP server is LAN-only — never expose it publicly without adding OAuth
- Always keep `README.md` and `CLAUDE.md` up to date when making changes
