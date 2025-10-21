 # syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

# ---- base image (shared tooling) ----
FROM php:${PHP_VERSION}-fpm-alpine AS base

# PHP extensions + composer (pdo_pgsql, intl for Symfony/L10n, opcache for prod)
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
      pdo_pgsql; \
  apk del .build-deps; \
  rm -rf /tmp/* /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --link ops/conf.d/10-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app

ENV APP_ENV=dev \
  COMPOSER_ALLOW_SUPERUSER=1 \
  SYMFONY_PHPUNIT_DIR=.symfony/phpunit

# ---- dev image (used by docker compose) ----
FROM base AS dev

RUN set -eux; \
    apk add --no-cache --virtual .xdebug-build \
        autoconf \
        build-base \
        linux-headers; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    apk del .xdebug-build; \
    rm -rf /tmp/* /var/cache/apk/*

COPY --link ops/conf.d/20-php.dev.ini /usr/local/etc/php/conf.d/

ENV XDEBUG_MODE=off

# compose bind-mounts ./backend onto /app, so we just need php-fpm
EXPOSE 9000
CMD ["php-fpm", "-F"]

# ---- vendor build (for prod) ----
FROM base AS vendor
ARG APP_ENV=prod
ENV APP_ENV=${APP_ENV}

# prevent the reinstallation of vendors at every changes in the source code
COPY --link backend/composer.* backend/symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link backend/ .

RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    chmod +x bin/console; sync;

# ---- production image ----
FROM base AS prod
ENV APP_ENV=prod

COPY --from=vendor /app /app
COPY --link ops/conf.d/20-php.prod.ini /usr/local/etc/php/conf.d/

EXPOSE 9000
CMD ["php-fpm", "-F"]