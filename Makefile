# =============================================================================
# Makefile - Docker Development & Deployment Commands
# =============================================================================

.PHONY: help build up down restart logs shell artisan migrate fresh queue test clean

# Default target
help:
	@echo "Usage: make [target]"
	@echo ""
	@echo "Docker Targets:"
	@echo "  build        Build Docker images"
	@echo "  up           Start all containers"
	@echo "  down         Stop all containers"
	@echo "  restart      Restart all containers"
	@echo "  logs         View container logs"
	@echo "  shell        Access app container shell"
	@echo ""
	@echo "Laravel Targets:"
	@echo "  artisan      Run artisan command (use: make artisan cmd='migrate')"
	@echo "  migrate      Run database migrations"
	@echo "  fresh        Fresh migration (WARNING: drops all tables)"
	@echo "  queue        Restart queue workers"
	@echo "  cache        Clear and rebuild caches"
	@echo "  test         Run tests"
	@echo ""
	@echo "SSR:"
	@echo "  ssr-restart  Restart SSR server (uses inertia:stop-ssr)"
	@echo "  ssr-check    Check SSR server health"
	@echo ""
	@echo "Maintenance:"
	@echo "  clean        Remove all containers, volumes, and images"
	@echo "  backup-db    Backup database"
	@echo "  backup-storage  Backup storage files"

# =============================================================================
# Docker Commands
# =============================================================================

build:
	cd docker && docker compose build

build-no-cache:
	cd docker && docker compose build --no-cache

up:
	cd docker && docker compose up -d

up-logs:
	cd docker && docker compose up

down:
	cd docker && docker compose down

restart:
	cd docker && docker compose restart

logs:
	cd docker && docker compose logs -f

logs-app:
	cd docker && docker compose logs -f app

shell:
	cd docker && docker compose exec app sh

# =============================================================================
# Development Mode
# =============================================================================

dev-up:
	cd docker && docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

dev-down:
	cd docker && docker compose -f docker-compose.yml -f docker-compose.dev.yml down

# =============================================================================
# PostgreSQL Mode
# =============================================================================

pg-up:
	cd docker && docker compose -f docker-compose.yml -f docker-compose.postgres.yml up -d

pg-down:
	cd docker && docker compose -f docker-compose.yml -f docker-compose.postgres.yml down

# =============================================================================
# Laravel Commands
# =============================================================================

artisan:
	cd docker && docker compose exec app php artisan $(cmd)

migrate:
	cd docker && docker compose exec app php artisan migrate --force --no-interaction

fresh:
	@echo "WARNING: This will drop all tables!"
	@read -p "Are you sure? [y/N] " confirm && [ "$$confirm" = "y" ] && \
	cd docker && docker compose exec app php artisan migrate:fresh --force

queue:
	cd docker && docker compose exec app supervisorctl restart queue-worker:*

cache:
	cd docker && docker compose exec app php artisan optimize:clear
	cd docker && docker compose exec app php artisan config:cache
	cd docker && docker compose exec app php artisan route:cache
	cd docker && docker compose exec app php artisan view:cache

test:
	cd docker && docker compose exec app php artisan test

# =============================================================================
# Supervisor Commands
# =============================================================================

supervisor-status:
	cd docker && docker compose exec app supervisorctl status

supervisor-restart:
	cd docker && docker compose exec app supervisorctl restart all

ssr-restart:
	cd docker && docker compose exec app supervisorctl restart ssr

ssr-check:
	cd docker && docker compose exec app php artisan inertia:check-ssr

# =============================================================================
# Maintenance Commands
# =============================================================================

clean:
	cd docker && docker compose down -v --rmi all --remove-orphans

backup-db:
	@mkdir -p backups
	cd docker && docker compose exec db mysqldump -u root -p$${DB_ROOT_PASSWORD} $${DB_DATABASE} > ../backups/db-$$(date +%Y%m%d-%H%M%S).sql
	@echo "Database backup saved to backups/"

backup-storage:
	@mkdir -p backups
	cd docker && docker compose exec app tar -czf /tmp/storage.tar.gz storage/app
	cd docker && docker cp $$(docker compose ps -q app):/tmp/storage.tar.gz ../backups/storage-$$(date +%Y%m%d-%H%M%S).tar.gz
	@echo "Storage backup saved to backups/"

# =============================================================================
# Health Check
# =============================================================================

health:
	@curl -sf http://localhost/up && echo "✓ Application is healthy" || echo "✗ Application is not responding"
