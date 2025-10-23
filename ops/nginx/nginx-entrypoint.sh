#!/bin/sh
set -eu

envsubst '$HTTPS_PORT' < /etc/nginx/templates/base.conf.tpl > /etc/nginx/conf.d/default.conf
exec nginx -g 'daemon off;'
