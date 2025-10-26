#!/bin/sh

set -eu

PROJECT_NAME="${COMPOSE_PROJECT_NAME:-${PROJECT_NAME:-invoicer}}"
CERT_VOLUME="${PROJECT_NAME}_certs"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

docker volume create "$CERT_VOLUME" >/dev/null 2>&1 || true

docker run --rm -v "$TMP_DIR":/certs alpine/mkcert \
  -key-file /certs/server.key \
  -cert-file /certs/server.crt \
  "localhost" 127.0.0.1 ::1

docker run --rm -v "$TMP_DIR":/src -v "$CERT_VOLUME":/dest busybox:1.36.1 \
  sh -c 'cp /src/server.* /dest/'

echo "Self-signed certificate copied to Docker volume $CERT_VOLUME (server.crt, server.key)."