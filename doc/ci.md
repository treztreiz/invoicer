# Continuous Integration Blueprint

This document captures the agreed-upon GitHub Actions setup. When implementing or updating
`.github/workflows/ci.yml`, follow these conventions.

## Triggers

Run on:

- Pull requests targeting `main` or `develop`.
- Pushes to `main`.
- Manual dispatch (`workflow_dispatch`) for ad-hoc runs.

Use workflow concurrency (`ci-${{ github.ref }}`) to cancel superseded runs.

## Jobs Overview

| Job                | Purpose                                  | Notes                                                     |
|--------------------|------------------------------------------|-----------------------------------------------------------|
| `api-unit`         | PHP static analysis + PHPUnit unit suite | Runs on PHP 8.4 with no containers; caches Composer deps. |
| `web-unit`         | ESLint/TypeScript/Vitest unit suite      | Uses npm/pnpm cache.                                      |
| `detect-changes`   | Path filter to skip downstream jobs      | Uses `dorny/paths-filter` output.                         |
| `api-functional`   | PHPUnit integration/functional suites    | Spins up PG service; only runs if `api/` files changed.   |
| `api-e2e` (future) | Dockerized smoke/tests on main or manual | Uses docker compose; optional.                            |
| `web-e2e` (future) | Frontend e2e against API service         | Launches after API jobs succeed.                          |

### api-unit

- Restore Composer cache keyed by `composer.lock`.
- `composer install` (no dev-scripts). Run `composer phpstan`, `composer php-cs-fixer:check`,
  `phpunit --testsuite Unit`.
- Publish JUnit/coverage artefacts only on failure; short retention.

### web-unit

- Restore npm/pnpm cache keyed by lockfile.
- `npm ci` (or `pnpm install --frozen-lockfile`). Run lint + unit tests.

### detect-changes

- Runs `dorny/paths-filter@v3` to detect `api/` vs `web/` changes.
- Downstream jobs gate on outputs (`needs.detect-changes.outputs.api == 'true'`, etc.).

### api-functional

- Needs: `api-unit`, `detect-changes`.
- `if: needs.detect-changes.outputs.api == 'true'`.
- Starts PostgreSQL 16 via `services`. Waits on health check.
- Installs dependencies; runs database migrations if needed; executes `phpunit --testsuite Integration,Functional` (now
  covering the schema round-trip test).

### api-e2e (future)

- Optional job for main branch or manual dispatch.
- Runs full docker compose stack; executes smoke tests.
- Publishes container logs on failure.

### web-e2e (future)

- Depends on API jobs; runs Playwright/Cypress against the API service.

## Caching & Artefacts

- Composer cache: `composer-${{ runner.os }}-${{ hashFiles('composer.lock') }}`.
- npm cache: `node-${{ runner.os }}-${{ hashFiles('**/package-lock.json') }}` (or pnpm equivalent).
- Docker layer caching (future) via `actions/cache` scoped to SHA.
- Artefacts (JUnit, logs) retained 3 days; upload only on failure where practical.

## Path Filters

Example filter config for `detect-changes`:

```yaml
filters: |
    api:
      - 'api/**'
      - 'composer.*'
    web:
      - 'web/**'
      - 'package-lock.json'
```

Jobs guard with `if:` blocks to skip when no relevant files changed.

## Environment Configuration

- Use environment variables to point Doctrine to the PG service (e.g.
  `DATABASE_URL=postgresql://user:pass@localhost:5432/db`).
- For frontend e2e, configure test tooling to point to the backend service port.

## Documentation References

- Update this document whenever workflow structure changes.
- CI job naming mirrors service directory names (`api`, `web`).
