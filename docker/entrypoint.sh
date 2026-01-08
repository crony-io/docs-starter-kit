#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

create_directories() {
    log_info "Creating required directories..."
    mkdir -p /var/log/{php,nginx,supervisor} /run/php
    mkdir -p /var/www/html/storage/{app/public,framework/{cache/data,sessions,views},logs,media-library}
    mkdir -p /var/www/html/bootstrap/cache
    chown -R www:www /var/www/html/{storage,bootstrap/cache}
    chown -R www:www /run/php
    chmod -R 775 /var/www/html/{storage,bootstrap/cache}
}

wait_for_database() {
    [ "$DB_CONNECTION" = "sqlite" ] && { log_info "Using SQLite, skipping database wait..."; return 0; }
    log_info "Waiting for database connection..."
    local max_attempts=30 attempt=1
    while [ $attempt -le $max_attempts ]; do
        if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
            mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null && { log_info "Database is ready!"; return 0; }
        elif [ "$DB_CONNECTION" = "pgsql" ]; then
            pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" 2>/dev/null && { log_info "Database is ready!"; return 0; }
        fi
        log_warn "Database not ready yet... (attempt $attempt/$max_attempts)"
        sleep 2
        attempt=$((attempt + 1))
    done
    log_error "Database connection timeout!"
    exit 1
}

wait_for_redis() {
    [ -z "$REDIS_HOST" ] || [ "$REDIS_HOST" = "null" ] && { log_info "Redis not configured, skipping..."; return 0; }
    log_info "Waiting for Redis connection..."
    local max_attempts=15 attempt=1
    local redis_auth=""
    [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ] && redis_auth="-a $REDIS_PASSWORD"
    while [ $attempt -le $max_attempts ]; do
        redis-cli -h "$REDIS_HOST" -p "${REDIS_PORT:-6379}" $redis_auth ping 2>/dev/null | grep -q "PONG" && { log_info "Redis is ready!"; return 0; }
        log_warn "Redis not ready yet... (attempt $attempt/$max_attempts)"
        sleep 1
        attempt=$((attempt + 1))
    done
    log_warn "Redis connection timeout - continuing anyway..."
}

run_laravel_setup() {
    log_info "Running Laravel setup tasks..."
    cd /var/www/html
    
    [ -z "$APP_KEY" ] && { log_warn "APP_KEY not set, generating new key..."; php artisan key:generate --force; }
    
    if [ "$APP_ENV" = "production" ]; then
        log_info "Optimizing for production..."
        php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
    else
        log_info "Clearing caches for development..."
        php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan event:clear
    fi
    
    log_info "Running database migrations..."
    php artisan migrate --force --no-interaction
    
    [ ! -L /var/www/html/public/storage ] && { log_info "Creating storage symlink..."; php artisan storage:link; }
    log_info "Laravel setup complete!"
}

main() {
    log_info "Starting application initialization..."
    create_directories
    wait_for_database
    wait_for_redis
    run_laravel_setup
    log_info "Initialization complete! Starting services..."
    exec "$@"
}

main "$@"
