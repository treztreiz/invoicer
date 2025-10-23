# Configuration Overview

## Environment files

- `.env` (tracked) holds shared defaults for local development: project name, image tag suffixes, version pins, DB
  credentials, dev TLS ports, etc.
- `.env.local` (ignored) can override any variable for the current machine. It is loaded after `.env` so values take
  precedence.
- Legacy Symfony `.env` files inside `api/` are no longer relied upon; all Symfony env vars flow from the top-level
  `.env` or `.env.local`.

## Makefile & Compose

`make` targets export the merged environment (`.env` + `.env.local`) before invoking `docker compose`. Key variables
propagated to containers include:

| Variable                                     | Purpose                                                                     |
|----------------------------------------------|-----------------------------------------------------------------------------|
| `PROJECT_NAME`                               | Compose project, image tags, shared volumes (`${PROJECT_NAME}_certs`).      |
| `DEV_TAG`/`PROD_TAG`                         | Stage-specific image tag suffixes for multi-stage builds.                   |
| `PHP_VERSION`/`NODE_VERSION`/`NGINX_VERSION` | Build args for multi-stage Dockerfiles (api, web).                          |
| `WEB_PUBLISHED_(TLS_)PORT`                   | HTTPS redirect target in dev (`HTTPS_PORT` env passed to nginx entrypoint). |
| `POSTGRES_*` / `APP_SECRET`                  | Symfony/Doctrine configuration supplied to the `api` container.             |

The base compose file (`ops/compose.base.yaml`) defines shared services/volumes; `ops/compose.dev.yaml` layers on
dev-only behavior, while `ops/compose.prod.yaml` references built images. The Makefile surfaces them via

- `make build` → `docker compose -f ops/compose.base.yaml -f ops/compose.dev.yaml build`
- `make up` → same with `up -d --wait`
- `make build-prod` → explicit `docker build` for prod stages
- `make swarm-deploy` → `docker stack deploy -c ops/compose.base.yaml -c ops/compose.prod.yaml`

## Symfony environment

Symfony reads its config purely from environment variables supplied by Compose (`APP_ENV`, `APP_SECRET`, `DATABASE_URL`,
etc.). Legacy `.env` files inside `api/` are no longer required. For runtime details (PHP ini and Xdebug settings)
see [PHP runtime & Xdebug](php-runtime.md).

## CI / Deployment

No GitHub Actions workflows are committed yet, but the structure supports CI-driven configuration:

- Override or inject env vars in the pipeline (e.g., `PROJECT_NAME`, `PROD_TAG`, DB credentials, TLS certs).
- Reuse `make build-prod` or calls to `docker build`/`docker stack deploy` with the same Compose files.
- Use the shared `certs` volume naming (`${PROJECT_NAME}_certs`) so the Let’s Encrypt/renewal process aligns between CI
  and production.

Future CI documentation can extend this doc once workflows are in place.
