#!/bin/sh
set -eu

: "${HTTPS_PORT:=443}"

HTTPS_PORT="$HTTPS_PORT" envsubst '$HTTPS_PORT' < /etc/nginx/templates/base.conf.tpl > /etc/nginx/conf.d/default.conf
exec nginx -g 'daemon off;'
