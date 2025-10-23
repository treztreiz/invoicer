# Certificates

## Self-signed (development)

Run the helper script to generate/update the local certificate used by nginx in dev and Swarm rehearsals:

```bash
./ops/nginx/certs/generate-self-signed.sh
```

The script writes `server.crt`/`server.key` directly into the Docker volume `${COMPOSE_PROJECT_NAME:-invoicer}_certs`,
which is mounted by the nginx container in both dev and prod. No files are stored in the repo.

## Let's Encrypt (production)

`./ops/nginx/certs/generate-letsencrypt.sh` is a placeholder for the future certbot-based workflow. Once implemented it
will populate the same `server.{crt,key}` files inside the volume.
