# TLS Certificates

## Development (self-signed)

Generate the self-signed certificate used by nginx locally and during Swarm rehearsals:

```bash
./ops/nginx/certs/generate-self-signed.sh
```

The script writes `server.crt` and `server.key` into the Docker volume `${COMPOSE_PROJECT_NAME:-invoicer}_certs`. Both dev (`make up`) and production rehearsals (`make swarm-deploy`) mount this volume automatically, so no files stay in git.

## Production (Let's Encrypt)

`./ops/nginx/certs/generate-letsencrypt.sh` is a placeholder for the future certbot workflow. Once implemented it will populate the same `server.{crt,key}` files in the shared volume after completing the ACME challenge.
