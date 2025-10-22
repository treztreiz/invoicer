# Dev TLS Certificates

You can generate the self-signed certificates needed by nginx without installing mkcert locally by using the official
container image.

Generate the cert + key (from the repo root):

```bash
mkdir -p ops/certs/dev
mkdir -p ops/certs/prod

# Local dev certs (localhost)
docker run --rm -v "$PWD/ops/certs/dev":/certs alpine/mkcert -key-file /certs/dev.key -cert-file /certs/dev.crt "localhost" 127.0.0.1 ::1

# Optional: mock prod cert for local Swarm tests (replace example.com with your domain)
docker run --rm -v "$PWD/ops/certs/prod":/certs alpine/mkcert -key-file /certs/prod.key -cert-file /certs/prod.crt "invoices.example.com"
```

After generating the certificates, restart nginx to pick them up (HTTPS on `https://localhost:8000`, HTTP redirect on `http://localhost:8080`):

```bash
docker compose -f ops/compose.base.yaml -f ops/compose.dev.yaml up -d --force-recreate web
```

For Swarm tests, seed the `invoicer_test_certs` volume (or the volume matching your stack name) so nginx can read the
PEM files:

```bash
docker run --rm \
  -v "$PWD/ops/certs/prod":/src \
  -v invoicer_test_certs:/dest \
  busybox:1.36.1 cp /src/prod.crt /dest/fullchain.pem

docker run --rm \
  -v "$PWD/ops/certs/prod":/src \
  -v invoicer_test_certs:/dest \
  busybox:1.36.1 cp /src/prod.key /dest/privkey.pem
```

The cert files remain git-ignored and must be regenerated per developer/test environment.
