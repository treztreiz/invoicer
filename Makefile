# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# CONFIGURATION
# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

# Expose all .env variables to the commands
ifneq (,$(wildcard .env))
include .env
export $(shell sed -n 's/^\([A-Za-z0-9_]\+\)=.*/\1/p' .env)
endif

# Expose all .env.local variables to the commands (overwrite .env variables)
ifneq (,$(wildcard .env.local))
include .env.local
export $(shell sed -n 's/^\([A-Za-z0-9_]\+\)=.*/\1/p' .env.local)
endif

# Expose project name to the commands
# (see https://docs.docker.com/compose/how-tos/project-name/#set-a-project-name)
export COMPOSE_PROJECT_NAME := $(PROJECT_NAME)

# compose/stack files
DEV_FILES := -f ops/compose.base.yaml -f ops/compose.dev.yaml
PROD_FILES := -c ops/compose.base.yaml -c ops/compose.prod.yaml

# docker compose helper
COMPOSE := docker compose $(DEV_FILES)

# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# COMMANDS
# //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

.PHONY: help certs build up down logs restart\:web debug\:on debug\:off shell\:api build\:prod swarm\:deploy swarm\:rm test

help:
	@echo "Dev helpers:"
	@echo "  make certs          # Generate dev/prod self-signed certs and seed Swarm volume"
	@echo "  make build          # Build dev images"
	@echo "  make up             # Start dev stack"
	@echo "  make down           # Stop dev stack"
	@echo "  make logs           # Tail dev stack logs"
	@echo "  make restart:web    # Recreate nginx (web) service"
	@echo "  make shell:api      # Enter backend container (api)"
	@echo "  make debug:on       # Enable Xdebug (api) and restart service"
	@echo "  make debug:off      # Disable Xdebug and restart service"
	@echo "  make test           # Run backend test suite inside the api container"
	@echo "  make build:prod     # Build prod images (api/web)"
	@echo "  make swarm:deploy   # Deploy stack locally for prod rehearsal"
	@echo "  make swarm:rm       # Remove local stack"

certs:
	./ops/nginx/certs/generate-self-signed.sh

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d --wait

down:
	$(COMPOSE) down --remove-orphans

logs:
	$(COMPOSE) logs -f

restart\:web:
	$(COMPOSE) up -d --force-recreate web

debug\:on:
	XDEBUG_MODE=coverage,develop,debug $(COMPOSE) up -d --force-recreate api

debug\:off:
	XDEBUG_MODE=off $(COMPOSE) up -d --force-recreate api

shell\:api:
	$(COMPOSE) exec api bash

build\:prod:
	docker build -f ops/images/api.Dockerfile --target prod \
		--build-arg PHP_VERSION=$(PHP_VERSION) \
		-t $(PROJECT_NAME)-api:$(PROD_TAG) .
	docker build -f ops/images/web.Dockerfile --target prod \
		--build-arg NODE_VERSION=$(NODE_VERSION) \
		--build-arg NGINX_VERSION=$(NGINX_VERSION) \
		-t $(PROJECT_NAME)-web:$(PROD_TAG) .

swarm\:deploy:
	docker stack deploy $(PROD_FILES) \
		--detach=true --with-registry-auth --resolve-image never $(PROJECT_NAME)

swarm\:rm:
	docker stack rm $(PROJECT_NAME)

test:
	$(COMPOSE) exec api composer phpunit
