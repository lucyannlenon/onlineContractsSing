# ========================
# CONFIG
# ========================
REMOTE_HOST ?= izi-apps
REMOTE_PATH ?= /Docker/izi-contrato-online
ENV_FILE ?= .env.local.prod
DIST_DIR ?= dist

# ========================
# BUILD
# ========================
vendor:
	docker run --rm \
		-v $(shell pwd):/app \
		-w /app \
		composer:2 install --no-dev --no-interaction --optimize-autoloader

# ========================
# PREPARE DIST
# ========================
prepare:
	@if [ ! -f $(ENV_FILE) ]; then \
		echo "$(ENV_FILE) nao encontrado."; \
		echo "Copie e preencha as variaveis de producao antes do deploy."; \
		exit 1; \
	fi

	rm -rf $(DIST_DIR)
	mkdir -p $(DIST_DIR)

	rsync -av \
		--exclude=$(DIST_DIR) \
		--exclude=.git \
		--exclude=.idea \
		--exclude=db_data \
		--exclude=var \
		--exclude=.env.local \
		--exclude=.env.signature-test \
		--exclude=docker-compose.signature-test.yml \
		./ $(DIST_DIR)/

	cp $(ENV_FILE) $(DIST_DIR)/.env
	cp docker-compose-production.yml $(DIST_DIR)/docker-compose.yml

# ========================
# REMOTE
# ========================
stop:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) 2>/dev/null && docker compose down 2>/dev/null || true \
	"

deploy:
	rsync -rz \
		--delete \
		--delete-after \
		--exclude=var \
		--exclude=db_data \
		$(DIST_DIR)/ $(REMOTE_HOST):$(REMOTE_PATH)

remote-rebuild:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose build --no-cache && \
		docker compose up -d --wait --remove-orphans --force-recreate && \
		docker image prune -f \
	"

remote-build:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose build php && \
		docker compose up -d --wait --remove-orphans && \
		docker image prune -f \
	"

remote:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose up -d --wait --no-build --remove-orphans \
	"

# ========================
# APP TASKS
# ========================
migrate:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction \
	"

keys:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec php php bin/console app:generate-keys \
	"

cache:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec php php bin/console cache:clear --env=prod && \
		docker compose exec php php bin/console cache:warmup --env=prod \
	"

# ========================
# CLEANUP
# ========================
end:
	rm -rf $(DIST_DIR)

prune:
	ssh $(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && docker system prune -f \
	"

# `release` e `update` fazem deploy de codigo sem rebuildar imagem.
# Use `bootstrap` no primeiro deploy e `infra` quando Dockerfile/compose mudarem.
release: vendor prepare deploy remote migrate cache end
update: release
bootstrap: vendor prepare deploy remote-build keys migrate cache end prune
infra: vendor prepare deploy remote-rebuild migrate cache end prune
