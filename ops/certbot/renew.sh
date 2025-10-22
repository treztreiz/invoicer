#!/usr/bin/env sh
set -eu

: "${CERTBOT_EMAIL:?CERTBOT_EMAIL environment variable required}"
: "${CERTBOT_DOMAINS:?CERTBOT_DOMAINS environment variable required}"

STACK_NAME="${STACK_NAME:-invoicer}"
DOCKER_BIN="${DOCKER_BIN:-docker}"
LE_VOLUME="${LE_VOLUME:-${STACK_NAME}_letsencrypt}"
ACME_VOLUME="${ACME_VOLUME:-${STACK_NAME}_acme}"
CERTS_VOLUME="${CERTS_VOLUME:-${STACK_NAME}_certs}"
WEB_SERVICE="${WEB_SERVICE:-${STACK_NAME}_web}"
CERTBOT_SERVER="${CERTBOT_SERVER:-}"
CERTBOT_EXTRA_ARGS="${CERTBOT_EXTRA_ARGS:-}"

PRIMARY_DOMAIN="$(printf '%s' "$CERTBOT_DOMAINS" | awk '{print $1}')"

# Ensure required volumes exist (in case stack has not created them yet)
for VOLUME in "$LE_VOLUME" "$ACME_VOLUME" "$CERTS_VOLUME"; do
  if ! $DOCKER_BIN volume inspect "$VOLUME" >/dev/null 2>&1; then
    $DOCKER_BIN volume create "$VOLUME" >/dev/null
  fi
done

# Obtain/renew certificates
$DOCKER_BIN run --rm \
  -v "${LE_VOLUME}:/etc/letsencrypt" \
  -v "${ACME_VOLUME}:/var/www/certbot" \
  certbot/certbot certonly --webroot \
  --webroot-path /var/www/certbot \
  --agree-tos --non-interactive \
  --email "$CERTBOT_EMAIL" \
  $( [ -n "$CERTBOT_SERVER" ] && printf ' --server %s' "$CERTBOT_SERVER" ) \
  $(printf ' -d %s' $CERTBOT_DOMAINS) \
  $CERTBOT_EXTRA_ARGS

# Copy the latest certs where nginx expects them
$DOCKER_BIN run --rm \
  -v "${LE_VOLUME}:/etc/letsencrypt:ro" \
  -v "${CERTS_VOLUME}:/dest" \
  alpine:3 sh -eu -c "cp /etc/letsencrypt/live/${PRIMARY_DOMAIN}/fullchain.pem /dest/fullchain.pem && cp /etc/letsencrypt/live/${PRIMARY_DOMAIN}/privkey.pem /dest/privkey.pem"

# Reload nginx (send HUP to each running container)
for CID in $($DOCKER_BIN ps --filter "name=${WEB_SERVICE}." --format '{{.ID}}'); do
  $DOCKER_BIN kill -s HUP "$CID"
done
