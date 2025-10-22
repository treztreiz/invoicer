# Certbot (Production TLS)

This directory contains the helper script used to obtain and renew production certificates while keeping the Swarm stack immutable.

## Volumes used by the stack

- `${STACK_NAME}_acme` – shared with nginx for `/.well-known/acme-challenge` HTTP-01 files.
- `${STACK_NAME}_letsencrypt` – stores Certbot configuration and issued certificates.
- `${STACK_NAME}_certs` – contains the plain PEM files (`fullchain.pem`, `privkey.pem`) that nginx reads.

## First issuance

1. Deploy the Swarm stack (nginx must already serve HTTP on port 80 with the `acme` volume mounted).
2. Run the script once with the required environment variables:

   ```bash
   STACK_NAME=invoicer \
   CERTBOT_EMAIL=you@example.com \
   CERTBOT_DOMAINS="invoices.example.com" \
   ops/certbot/renew.sh
   ```

   Adjust `CERTBOT_DOMAINS` for additional SANs (space-separated). The script will issue certificates and copy them into the `certs` volume.

## Renewals via host cron/systemd

Create a timer/cron job on the host, for example (monthly at 03:30):

```cron
30 3 * * * STACK_NAME=invoicer CERTBOT_EMAIL=you@example.com CERTBOT_DOMAINS="invoices.example.com" /path/to/repo/ops/certbot/renew.sh >> /var/log/invoicer-certbot.log 2>&1
```

The script runs Certbot in a container, syncs the PEM files into the shared `certs` volume, and sends `SIGHUP` to the nginx containers so the new certificate takes effect without downtime.

Advanced options:

- Set `CERTBOT_SERVER` to target the Let’s Encrypt staging endpoint during testing.
- Append extra flags via `CERTBOT_EXTRA_ARGS` (e.g., `--rsa-key-size 4096`).

## Notes

- The script assumes the Docker CLI is available as `docker`. Override with `DOCKER_BIN` if needed.
- Ensure the host firewall allows outbound ports 80/443 for the HTTP-01 challenge.
- For staging/preview environments, set `CERTBOT_SERVER=https://acme-staging-v02.api.letsencrypt.org/directory` in the environment before running the script to avoid rate limits.
