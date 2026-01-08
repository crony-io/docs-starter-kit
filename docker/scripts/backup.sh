#!/bin/bash
# =============================================================================
# Backup Script - Database and Storage
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
DOCKER_DIR="$PROJECT_DIR/docker"
BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Load environment
source "$DOCKER_DIR/.env"

# -----------------------------------------------------------------------------
# Database Backup
# -----------------------------------------------------------------------------
backup_database() {
    log_info "Backing up database..."
    
    cd "$DOCKER_DIR"
    
    if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
        docker compose exec -T db mysqldump \
            -u"$DB_USERNAME" \
            -p"$DB_PASSWORD" \
            "$DB_DATABASE" > "$BACKUP_DIR/db-$TIMESTAMP.sql"
    elif [ "$DB_CONNECTION" = "pgsql" ]; then
        docker compose exec -T db pg_dump \
            -U "$DB_USERNAME" \
            "$DB_DATABASE" > "$BACKUP_DIR/db-$TIMESTAMP.sql"
    else
        log_warn "Unknown database connection: $DB_CONNECTION"
        return 1
    fi
    
    # Compress
    gzip "$BACKUP_DIR/db-$TIMESTAMP.sql"
    
    log_info "Database backup saved: $BACKUP_DIR/db-$TIMESTAMP.sql.gz"
}

# -----------------------------------------------------------------------------
# Storage Backup
# -----------------------------------------------------------------------------
backup_storage() {
    log_info "Backing up storage..."
    
    cd "$DOCKER_DIR"
    
    # Create tar archive in container
    docker compose exec -T app tar -czf /tmp/storage-backup.tar.gz \
        -C /var/www/html storage/app
    
    # Copy to host
    docker cp "$(docker compose ps -q app)":/tmp/storage-backup.tar.gz \
        "$BACKUP_DIR/storage-$TIMESTAMP.tar.gz"
    
    # Cleanup
    docker compose exec -T app rm -f /tmp/storage-backup.tar.gz
    
    log_info "Storage backup saved: $BACKUP_DIR/storage-$TIMESTAMP.tar.gz"
}

# -----------------------------------------------------------------------------
# Cleanup old backups (keep last 7 days)
# -----------------------------------------------------------------------------
cleanup_old_backups() {
    log_info "Cleaning up old backups (keeping last 7 days)..."
    
    find "$BACKUP_DIR" -name "db-*.sql.gz" -mtime +7 -delete 2>/dev/null || true
    find "$BACKUP_DIR" -name "storage-*.tar.gz" -mtime +7 -delete 2>/dev/null || true
    
    log_info "Cleanup complete"
}

# -----------------------------------------------------------------------------
# Main
# -----------------------------------------------------------------------------
main() {
    case "${1:-all}" in
        db|database)
            backup_database
            ;;
        storage)
            backup_storage
            ;;
        all)
            backup_database
            backup_storage
            cleanup_old_backups
            ;;
        cleanup)
            cleanup_old_backups
            ;;
        *)
            echo "Usage: $0 {db|storage|all|cleanup}"
            exit 1
            ;;
    esac
    
    log_info "Backup completed successfully!"
    echo ""
    log_info "Backups stored in: $BACKUP_DIR"
    ls -lh "$BACKUP_DIR"
}

main "$@"
