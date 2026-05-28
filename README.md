# Finance Analyzer

A self-hosted personal finance analyzer that uses AI to categorize transactions and provide spending insights. Built with Laravel and React, it **exposes its own MCP server** so you connect your own Claude client (Claude Desktop / Code) to do the analysis — no AI runs locally and the app holds no LLM API key.

## Architecture

```
Your Claude client (Claude Desktop / Code)
      ↓ MCP over LAN (Sanctum bearer token)
Laravel Backend + MCP server (this service)
      ↓  PII sanitized at the egress boundary
React Frontend (this service)  +  MySQL 8
```

Transaction data is sanitized by `TransactionSanitizer` before any of it leaves the app:
a user-defined replacement map (encrypted) swaps account numbers/names for friendly labels,
and a regex fallback redacts or pseudonymises anything else. `raw_text` is never exposed.
Every MCP response is logged for audit ("what did Claude see?" on the Privacy page).

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3
- **Frontend:** React 19, TypeScript, Inertia.js, Tailwind CSS v4, Recharts
- **Database:** MySQL 8
- **Auth:** Laravel Sanctum
- **Queue:** Database driver
- **Containerization:** Docker

## Prerequisites

- Docker & Docker Compose
- Git

## Quick Start

```bash
# Clone the repo
git clone git@github.com:Lawlage/finance-app.git
cd finance-app

# Copy environment file and configure
cp .env.example .env

# Build and start containers
make build
make up

# Install dependencies (first time only)
docker compose exec app composer install
docker compose exec app npm install

# Generate app key and run migrations
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# Start the Vite dev server
make dev
```

Visit http://localhost:8080 to access the app.

## Docker Services

| Service | Description              | Port       |
|---------|--------------------------|------------|
| app     | PHP-FPM + Node.js        | 5173 (HMR) |
| nginx   | Web server               | 8080       |
| mysql   | MySQL 8 database         | Internal   |

## Development Commands

```bash
make up              # Start containers
make down            # Stop containers
make shell           # Shell into app container
make dev             # Start Vite dev server
make queue           # Start queue worker
make migrate         # Run migrations
make fresh           # Fresh migrate + seed
```

## Testing

All test layers enforce 90% minimum coverage.

```bash
make test-backend       # Run PHP tests (Pest)
make test-backend-cover # With coverage check
make test-frontend      # Run JS tests (Vitest)
make test-frontend-cover # With coverage check
make test-e2e           # Run E2E tests (Playwright)
make ci                 # Full local CI (lint + test)
```

## Code Quality

```bash
make lint            # Run all linters
make lint-backend    # Pint + Larastan + Rector
make lint-frontend   # tsc + ESLint + Prettier
make format          # Auto-fix formatting
make analyse         # Static analysis only
```

## Project Structure

```
├── app/                    # Laravel application code
│   ├── Http/Controllers/   # Inertia controllers
│   ├── Jobs/               # Queue jobs
│   ├── Mcp/                # MCP server, resources, tools, prompts
│   ├── Models/             # Eloquent models
│   └── Services/           # TransactionSanitizer, WestpacCsvParser
├── resources/js/           # React frontend
│   ├── Pages/              # Inertia page components
│   └── Components/         # Reusable components
├── routes/ai.php           # MCP server registration
├── database/migrations/    # Database migrations
├── tests/                  # Test suites
├── docker/                 # Docker configuration
├── .github/workflows/      # CI/CD pipeline
└── docker-compose.yml      # Docker services
```

## Environment Variables

See `.env.example` for all required variables. Key settings:

- `DB_*` — MySQL connection (defaults work with Docker)
- `APP_KEY` — also encrypts the PII replacement map and seeds pseudonym hashing

## Connecting Claude (MCP)

The app exposes a local MCP server at `/mcp/finance` (LAN only, no public exposure).

1. Mint a Sanctum token: `docker compose exec app php artisan tinker` →
   `$user->createToken('claude')->plainTextToken`
2. Point your Claude client at `http://<host>:8080/mcp/finance` with header
   `Authorization: Bearer <token>`.
3. Ask Claude to run the `analyze_spending` prompt or categorize uncovered transactions.

Inspect/debug locally with `docker compose exec app php artisan mcp:inspector finance`.
Manage the PII replacement map, sanitization mode, and egress audit on the **Privacy & MCP** page.

## CI/CD

GitHub Actions runs 5 jobs on push/PR to main:

1. **backend-lint** — Pint, Larastan (level 9), Rector
2. **backend-test** — Pest with 90% coverage
3. **frontend-lint** — TypeScript, ESLint, Prettier
4. **frontend-test** — Vitest with 90% coverage
5. **e2e** — Playwright (depends on test jobs passing)
