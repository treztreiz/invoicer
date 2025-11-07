# Invoicer MVP

Single-user invoicing/quoting application built to demonstrate a clean hexagonal architecture with Symfony (API) and
Vite/React (web UI). Docker drives both development and the single-host (Swarm) deployment path.

## Running Locally

```bash
# install dependencies, build images, run stack
make certs
make build
make up

# tail logs / stop
make logs
make down
```

### Services in dev (`make up`)

- `api` – Symfony 7 API with PHP-FPM
- `database` – PostgreSQL
- `webapp` – Vite dev server (HMR)
- `web` – nginx reverse proxy (HTTPS, TLS parity)

## Directory layout

```
api/            # Symfony API Platform back end
webapp/         # React/Vite front end
ops/            # Dockerfiles, nginx configs, cert scripts
  ├── images/   # Multi-stage build definitions (api, web)
  ├── nginx/    # nginx templates, TLS tooling
  ├── php/      # php configuration
  └── compose.* # base/dev/prod compose descriptors
```

## Docs

- [Configuration Overview](doc/config.md)
- [TLS certificates & scripts](doc/certs.md)
- [nginx runtime & templating](doc/nginx.md)
- [Docker images & stages](doc/images.md)
- [PHP runtime & Xdebug](doc/php-runtime.md)
- [Doctrine check-aware layer](doc/check-aware.md)
- [CI blueprint](doc/ci.md)
- [API Platform infra conventions](doc/api-platform.md)

## Toolchain

- PHP 8.4 / Symfony 7 API Platform
- PostgreSQL (Doctrine, UUIDv7 IDs)
- Vite + React 19 + TypeScript
- nginx reverse proxy (dev & prod parity)
- Docker/Compose for dev, Docker Swarm for single-host prod tests
