# Docker images & build strategy

## api (Symfony)

`ops/images/api.Dockerfile` uses four stages:

1. **base** – PHP-FPM with required extensions, composer installed, shared `.ini` (ops/php/10-php.ini) and default env.
2. **dev** – installs Xdebug and loads dev `.ini` overrides (ops/php/20-php.dev.ini). `make up` mounts `../api` into
   this stage.
3. **vendor** – installs composer dependencies with `--no-dev`, runs Symfony cache warmup/auto-scripts (
   `composer dump-autoload`, `dump-env`, `post-install-cmd`).
4. **prod** – copies the baked `/app` from `vendor` plus prod `.ini` overrides (ops/php/20-php.prod.ini).

`make build` → compose builds the `dev` stage.
`make build-prod` → `docker build ... --target prod` to produce the deployable image.

## web / webapp (React + nginx)

`ops/images/web.Dockerfile` also uses multi-stage builds:

1. **base** – Node image with `npm ci` + source copy.
2. **dev** – exposes Vite HMR (`npm run dev ...`). Compose uses this stage for `webapp` service.
3. **build** – produces the static bundle (`npm run build`).
4. **prod** – nginx runtime: copies the base template + prod include + entrypoint script, and pulls built assets from
   the `build` stage.

In dev, `webapp` runs the `dev` stage (Node), while the `web` service uses nginx with the same template so HTTPS/TLS
behavior mirrors prod.

## Compose integration

- Dev compose (`ops/compose.dev.yaml`) targets the `dev` stage for `api` and `webapp`. Bind mounts provide hot
  reloading.
- Prod compose (`ops/compose.prod.yaml`) references the images tagged by `make build-prod` (
  `$(PROJECT_NAME)-api:$(PROD_TAG)` and `$(PROJECT_NAME)-web:$(PROD_TAG)`).
- The Makefile exports build arguments from `.env` so both dev and prod builds stay consistent (PHP/Node/nginx versions,
  image tags, etc.).

Adjust the Dockerfiles or Makefile when introducing new stages or dependencies so this doc stays up to date.
