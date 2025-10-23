# nginx setup

## Entrypoint & templating

The stock `nginx:alpine` image keeps `/etc/nginx/nginx.conf` untouched; our config enters via
`/etc/nginx/conf.d/default.conf`.

At runtime `ops/nginx/nginx-entrypoint.sh` runs first (mounted in dev, baked into prod images):

1. Loads `HTTPS_PORT` (defaults to 443).
2. Substitutes the value into `ops/nginx/nginx.base.conf` via `envsubst`, writing to `/etc/nginx/conf.d/default.conf`.
3. Starts nginx (`daemon off`).

`nginx.base.conf` contains the shared HTTP/HTTPS servers: ACME handling, TLS settings, security headers, API proxy,
uploads handling. At the end it includes `/etc/nginx/conf.d/webapp`, which is provided by:

- `nginx.dev.conf` (Vite HMR proxy to `webapp:5173`, disables HSTS).
- `nginx.prod.conf` (serves compiled assets from `/usr/share/nginx/html`).

Both include files and the base template are mounted in dev (`ops/compose.dev.yaml`) and copied into the prod image (
`ops/images/web.Dockerfile`).

## TLS

- Certs are always expected at `/etc/nginx/certs/server.crt` / `server.key`.
- The shared Docker volume `${COMPOSE_PROJECT_NAME}_certs` stores these files.
- `generate-self-signed.sh` populates the volume with mkcert-generated certs for development; the same volume is used in
  Swarm rehearsals and production.
- Future Let’s Encrypt support will drop real certs into the same volume via `generate-letsencrypt.sh` (placeholder for
  now).

See [TLS Certificates](certs.md) for details about certificates generation and renewal.

## ACME challenges

The HTTP server (`listen 80`) allows GETs under `/.well-known/acme-challenge/` and serves files from `/var/www/certbot`.
In production this path is fulfilled by mounting the `acme` volume.

The HTTPS server returns `404` for that location so challenge files aren’t exposed once TLS is active.

## Additional hardening

- Port 80 server redirects to HTTPS using the templated `HTTPS_PORT` value.
- TLS 1.2/1.3 enforced with curated cipher suite.
- Security headers (`Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`).
- Rate limiting (`limit_req_zone` + `limit_req`) on `/api/` to deter brute-force attacks.
- Optional gzip compression enabled in production via `nginx.prod.conf`.

If you adjust nginx behavior (headers, gzip, rate limits), update this doc accordingly so the assumptions remain clear.
