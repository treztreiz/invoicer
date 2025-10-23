# PHP runtime & Xdebug

## PHP configuration

Shared ini fragments live under `ops/php/` and are copied into the image during the multi-stage build:

- `10-php.ini` – base settings applied to every stage (timezone, realpath cache, opcache defaults, etc.).
- `20-php.dev.ini` – dev-specific additions (e.g., Xdebug client host, optional autostart).
- `20-php.prod.ini` – prod-specific overrides (cache warmup, opcache tuning, HSTS expectations).

If you need to change PHP settings, edit the relevant file and rebuild (`make build` or `make build-prod`).

## Xdebug usage

Xdebug is installed only in the `dev` stage of `ops/images/api.Dockerfile`. The service starts with `XDEBUG_MODE=off` by
default for performance.

- `make debug-on` → recreates the `api` container with `XDEBUG_MODE=debug,develop` (other compose env values remain
  intact).
- `make debug-off` → switches it back to `off`.

Set your IDE to listen on `${COMPOSE_PROJECT_NAME:-invoicer}_api` host or use `host.docker.internal` as configured in
`.env`/`.env.local`. Additional Xdebug ini values (client port, autostart) can be tweaked in `ops/php/20-php.dev.ini`.

## Symfony environment

Symfony loads its environment purely from container env vars supplied by Compose:

- `APP_ENV` (default `dev` in dev compose, `prod` for prod deployments)
- `APP_SECRET`, `DATABASE_URL`, etc., all sourced from the top-level `.env`/`.env.local`

See [Configuration Overview](config.md) for how these variables are merged and exported.
