PROJECT_NAME ?= invoicer
COMPOSE_FILE := ops/compose.dev.yaml
COMPOSE := docker compose -f $(COMPOSE_FILE)

.PHONY: help certs up down logs web-restart debug-on debug-off build-prod swarm-deploy swarm-rm

help:
	@echo "Dev helpers:"
	@echo "  make certs          # Generate dev/prod self-signed certs and seed Swarm volume"
	@echo "  make up             # Start dev stack"
	@echo "  make down           # Stop dev stack"
	@echo "  make logs           # Tail dev stack logs"
	@echo "  make web-restart    # Recreate nginx (web) service"
	@echo "  make debug-on       # Enable Xdebug (backend) and restart service"
	@echo "  make debug-off      # Disable Xdebug and restart service"
	@echo "  make build-prod     # Build prod images (backend/frontend)"
	@echo "  make swarm-deploy   # Deploy stack locally for prod rehearsal"
	@echo "  make swarm-rm       # Remove local stack"

certs:
	mkdir -p ops/certs/dev ops/certs/prod
	docker run --rm -v "$$(pwd)"/ops/certs/dev:/certs alpine/mkcert \
		-key-file /certs/dev.key -cert-file /certs/dev.crt "localhost" 127.0.0.1 ::1
	docker run --rm -v "$$(pwd)"/ops/certs/prod:/certs alpine/mkcert \
		-key-file /certs/prod.key -cert-file /certs/prod.crt "invoices.local"
	docker run --rm \
		-v "$$(pwd)"/ops/certs/prod:/src \
		-v $(PROJECT_NAME)_certs:/dest \
		busybox cp /src/prod.crt /dest/fullchain.pem
	docker run --rm \
		-v "$$(pwd)"/ops/certs/prod:/src \
		-v $(PROJECT_NAME)_certs:/dest \
		busybox cp /src/prod.key /dest/privkey.pem

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

build:
	$(COMPOSE) build

build-prod:
	docker build -f ops/backend.Dockerfile --target prod -t $(PROJECT_NAME)-backend:local .
	docker build -f ops/frontend.Dockerfile --target prod -t $(PROJECT_NAME)-web:local .

swarm-deploy:
	APP_SECRET=$$(openssl rand -hex 16) POSTGRES_PASSWORD=app BACKEND_IMAGE=$(PROJECT_NAME)-backend:local WEB_IMAGE=$(PROJECT_NAME)-web:local \
		docker stack deploy -c ops/docker-stack.yaml --with-registry-auth --resolve-image never --detach=true $(PROJECT_NAME)

swarm-rm:
	docker stack rm $(PROJECT_NAME)
