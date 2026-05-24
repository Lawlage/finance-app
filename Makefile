.PHONY: help up down build shell migrate fresh seed test test-backend test-backend-cover \
       test-frontend test-frontend-cover test-e2e lint lint-backend lint-frontend \
       format analyse ci queue dev

# ── Docker ────────────────────────────────────────────────
up: ## Start all containers
	docker compose up -d

down: ## Stop all containers
	docker compose down

build: ## Build all containers
	docker compose build

shell: ## Open a shell in the app container
	docker compose exec app sh

# ── Development ───────────────────────────────────────────
dev: ## Start Vite dev server
	docker compose exec app npm run dev

queue: ## Start the queue worker
	docker compose exec app php artisan queue:work --tries=3

# ── Database ──────────────────────────────────────────────
migrate: ## Run migrations
	docker compose exec app php artisan migrate

fresh: ## Fresh migrate with seeding
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec app php artisan db:seed

# ── Backend Tests ─────────────────────────────────────────
test-backend: ## Run backend tests (Pest)
	docker compose exec app ./vendor/bin/pest

test-backend-cover: ## Run backend tests with 90% coverage
	docker compose exec app ./vendor/bin/pest --coverage --min=90

# ── Frontend Tests ────────────────────────────────────────
test-frontend: ## Run frontend tests (Vitest)
	docker compose exec app npx vitest run

test-frontend-cover: ## Run frontend tests with 90% coverage
	docker compose exec app npx vitest run --coverage

# ── E2E Tests ─────────────────────────────────────────────
test-e2e: ## Run E2E tests (Playwright)
	npx playwright test

# ── All Tests ─────────────────────────────────────────────
test: test-backend test-frontend ## Run backend + frontend tests

# ── Linting ───────────────────────────────────────────────
lint-backend: ## Lint PHP (Pint, Larastan, Rector)
	docker compose exec app ./vendor/bin/pint --test
	docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M
	docker compose exec app ./vendor/bin/rector --dry-run

lint-frontend: ## Lint TypeScript (ESLint, Prettier, tsc)
	docker compose exec app npx tsc --noEmit
	docker compose exec app npx eslint resources/js/
	docker compose exec app npx prettier --check "resources/js/**/*.{ts,tsx,css,json}"

lint: lint-backend lint-frontend ## Run all linters

# ── Formatting ────────────────────────────────────────────
format: ## Auto-fix formatting issues
	docker compose exec app ./vendor/bin/pint
	docker compose exec app npx eslint --fix resources/js/
	docker compose exec app npx prettier --write "resources/js/**/*.{ts,tsx,css,json}"

# ── Static Analysis ──────────────────────────────────────
analyse: ## Run static analysis
	docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=512M
	docker compose exec app ./vendor/bin/rector --dry-run

# ── Full CI (pre-push hook) ──────────────────────────────
ci: lint test ## Full local CI suite (no E2E)

# ── Help ─────────────────────────────────────────────────
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
