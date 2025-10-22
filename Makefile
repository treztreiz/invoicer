ifneq (,$(wildcard .env))
include .env
export $(shell sed -n 's/^\([A-Za-z0-9_]\+\)=.*/\1/p' .env)
endif

PROJECT_NAME ?= invoicer
DEV_TAG ?= dev-local
PROD_TAG ?= prod-local

dev_compose_files := -f ops/compose.base.yaml -f ops/compose.dev.yaml
COMPOSE := docker compose $(dev_compose_files)

DEV_BACKEND_IMAGE := $(PROJECT_NAME)-backend:$(DEV_TAG)
DEV_FRONTEND_IMAGE := $(PROJECT_NAME)-frontend:$(DEV_TAG)
PROD_BACKEND_IMAGE := $(PROJECT_NAME)-backend:$(PROD_TAG)
PROD_WEB_IMAGE := $(PROJECT_NAME)-web:$(PROD_TAG)

export COMPOSE_PROJECT_NAME := $(PROJECT_NAME)

.PHONY: help setup-certs build up down logs web-restart debug-on debug-off build-prod swarm-deploy swarm-remove

help:
	@echo "Dev helpers:"
	@echo "  make setup-certs    # Generate dev/prod self-signed certs and seed Swarm volume"
	@echo "  make build          # Build dev images"
	@echo "  make up             # Start dev stack"
	@echo "  make down           # Stop dev stack"
	@echo "  make logs           # Tail dev stack logs"
	@echo "  make web-restart    # Recreate nginx (web) service"
	@echo "  make debug-on       # Enable Xdebug (backend) and restart service"
	@echo "  make debug-off      # Disable Xdebug and restart service"
	@echo "  make build-prod     # Build prod images (backend/frontend)"
	@echo "  make swarm-deploy   # Deploy stack locally for prod rehearsal"
	@echo "  make swarm-remove   # Remove local stack"

setup-certs:
	mkdir -p ops/certs/dev ops/certs/prod
	docker run --rm -v "$$PWD"/ops/certs/dev:/certs alpine/mkcert \
		-key-file /certs/dev.key -cert-file /certs/dev.crt "localhost" 127.0.0.1 ::1
	docker run --rm -v "$$PWD"/ops/certs/prod:/certs alpine/mkcert \
		-key-file /certs/prod.key -cert-file /certs/prod.crt "invoices.local"
	docker run --rm \
		-v "$$PWD"/ops/certs/prod:/src \
		-v $(PROJECT_NAME)_certs:/dest \
		busybox:1.36.1 cp /src/prod.crt /dest/fullchain.pem
	docker run --rm \
		-v "$$PWD"/ops/certs/prod:/src \
		-v $(PROJECT_NAME)_certs:/dest \
		busybox:1.36.1 cp /src/prod.key /dest/privkey.pem

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d --wait

down:
	$(COMPOSE) down --remove-orphans

logs:
	$(COMPOSE) logs -f

web-restart:
	$(COMPOSE) up -d --force-recreate web

debug-on:
	XDEBUG_MODE=debug,develop $(COMPOSE) up -d --force-recreate backend

debug-off:
	XDEBUG_MODE=off $(COMPOSE) up -d --force-recreate backend

build-prod:
	docker build -f ops/backend.Dockerfile --target prod \
		--build-arg PHP_VERSION=$(PHP_VERSION) \
		-t $(PROD_BACKEND_IMAGE) .
	docker build -f ops/frontend.Dockerfile --target prod \
		--build-arg NODE_VERSION=$(NODE_VERSION) \
		--build-arg NGINX_VERSION=$(NGINX_VERSION) \
		-t $(PROD_WEB_IMAGE) .

swarm-deploy:
	WEB_PUBLISHED_PORT=80 WEB_PUBLISHED_TLS_PORT=443 \
	PROD_BACKEND_IMAGE=$(PROD_BACKEND_IMAGE) \
	PROD_WEB_IMAGE=$(PROD_WEB_IMAGE) \
	PROJECT_NAME=$(PROJECT_NAME) \
	docker stack deploy -c ops/compose.base.yaml -c ops/compose.prod.yaml \
		--with-registry-auth --resolve-image never $(PROJECT_NAME)

swarm-remove:
	docker stack rm $(PROJECT_NAME)
