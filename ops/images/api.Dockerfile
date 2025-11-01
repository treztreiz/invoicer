 # syntax=docker/dockerfile:1

ARG PHP_VERSION

# ---- base image ----
FROM php:${PHP_VERSION?} AS base

ENV APP_ENV=dev \
  COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_CACHE_DIR=/tmp/composer-cache

WORKDIR /app

# packages & PHP extensions installation
RUN set -eux; \
  apk add --no-cache \
      bash \
      git \
      icu-dev \
      libpq \
      libpq-dev \
      unzip \
      zip; \
  apk add --no-cache --virtual .build-deps \
      autoconf \
      build-base; \
  pecl install apcu; \
  docker-php-ext-enable apcu; \
  docker-php-ext-install \
      intl \
      opcache \
      bcmath \
      pdo_pgsql; \
  apk del .build-deps; \
  rm -rf /tmp/* /var/cache/apk/*

# composer installation
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# php main configuration
COPY --link ops/php/10-php.ini /usr/local/etc/php/conf.d/

EXPOSE 9000
CMD ["php-fpm", "-F"]

# ---- dev image (used by docker compose) ----
FROM base AS dev

ENV XDEBUG_MODE=off

# xdebug installation
RUN set -eux; \
    apk add --no-cache --virtual .xdebug-build \
        autoconf \
        build-base \
        linux-headers; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    apk del .xdebug-build; \
    rm -rf /tmp/* /var/cache/apk/*

# php/xdebug configuration
COPY --link ops/php/20-php.dev.ini /usr/local/etc/php/conf.d/

# ---- vendor build (for prod) ----
FROM base AS vendor

ENV APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY --link api/composer.* api/symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link api/ .

# bake the Symfony app for production
RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    chmod +x bin/console; sync;

# ---- production image ----
FROM base AS prod

ENV APP_ENV=prod

# baked app
COPY --from=vendor /app /app
# php prod configuration
COPY --link ops/php/20-php.prod.ini /usr/local/etc/php/conf.d/