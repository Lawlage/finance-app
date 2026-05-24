# Finance Analyzer

A self-hosted personal finance analyzer that uses AI to categorize transactions and provide spending insights. Built with Laravel, React, and powered by a local LLM (Llama 3.3 70B) running on a separate machine.

## Architecture

```
React Frontend (this service)
      ↓
Laravel Backend (this service)
      ↓ HTTP + API key (LAN)
Python FastAPI AI Gateway (separate machine)
      ↓
Ollama (Llama 3.3 70B)
```

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
# Edit .env with your AI gateway IP and API key

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
│   ├── Models/             # Eloquent models
│   └── Services/           # AI gateway service
├── resources/js/           # React frontend
│   ├── Pages/              # Inertia page components
│   └── Components/         # Reusable components
├── database/migrations/    # Database migrations
├── tests/                  # Test suites
├── docker/                 # Docker configuration
├── .github/workflows/      # CI/CD pipeline
└── docker-compose.yml      # Docker services
```

## Environment Variables

See `.env.example` for all required variables. Key settings:

- `AI_GATEWAY_URL` — URL of the AI gateway on your LAN
- `AI_GATEWAY_API_KEY` — Shared secret for API authentication
- `DB_*` — MySQL connection (defaults work with Docker)

## CI/CD

GitHub Actions runs 5 jobs on push/PR to main:

1. **backend-lint** — Pint, Larastan (level 9), Rector
2. **backend-test** — Pest with 90% coverage
3. **frontend-lint** — TypeScript, ESLint, Prettier
4. **frontend-test** — Vitest with 90% coverage
5. **e2e** — Playwright (depends on test jobs passing)
