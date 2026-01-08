#!/bin/bash
# =============================================================================
# Production Deployment Script
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
DOCKER_DIR="$PROJECT_DIR/docker"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_step() { echo -e "${BLUE}[STEP]${NC} $1"; }

# -----------------------------------------------------------------------------
# Pre-flight checks
# -----------------------------------------------------------------------------
preflight_checks() {
    log_step "Running pre-flight checks..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    # Check Docker Compose
    if ! docker compose version &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
    
    # Check .env file
    if [ ! -f "$DOCKER_DIR/.env" ]; then
        log_error "Environment file not found: $DOCKER_DIR/.env"
        log_info "Copy .env.example to .env and configure it:"
        log_info "  cp $DOCKER_DIR/.env.example $DOCKER_DIR/.env"
        exit 1
    fi
    
    # Check APP_KEY
    source "$DOCKER_DIR/.env"
    if [ -z "$APP_KEY" ]; then
        log_warn "APP_KEY is not set. Generating one..."
        NEW_KEY=$(docker run --rm php:8.5-cli php -r "echo 'base64:' . base64_encode(random_bytes(32));")
        echo "APP_KEY=$NEW_KEY" >> "$DOCKER_DIR/.env"
        log_info "APP_KEY generated and added to .env"
    fi
    
    log_info "Pre-flight checks passed!"
}

# -----------------------------------------------------------------------------
# Build
# -----------------------------------------------------------------------------
build() {
    log_step "Building Docker images..."
    cd "$DOCKER_DIR"
    docker compose build --no-cache
    log_info "Build complete!"
}

# -----------------------------------------------------------------------------
# Deploy
# -----------------------------------------------------------------------------
deploy() {
    log_step "Deploying application..."
    cd "$DOCKER_DIR"
    
    # Pull latest images for dependencies
    docker compose pull db redis
    
    # Start services
    docker compose up -d
    
    # Wait for services
    log_info "Waiting for services to be ready..."
    sleep 10
    
    # Health check
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -sf http://localhost/up > /dev/null 2>&1; then
            log_info "Application is healthy!"
            break
        fi
        log_warn "Waiting for application... (attempt $attempt/$max_attempts)"
        sleep 2
        attempt=$((attempt + 1))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        log_error "Application health check failed!"
        log_info "Check logs with: docker compose logs app"
        exit 1
    fi
    
    log_info "Deployment complete!"
}

# -----------------------------------------------------------------------------
# Rollback
# -----------------------------------------------------------------------------
rollback() {
    log_step "Rolling back to previous version..."
    cd "$DOCKER_DIR"
    
    # Get previous image
    PREV_IMAGE=$(docker images --format "{{.Repository}}:{{.Tag}}" | grep docs-app | head -2 | tail -1)
    
    if [ -z "$PREV_IMAGE" ]; then
        log_error "No previous image found for rollback"
        exit 1
    fi
    
    log_info "Rolling back to: $PREV_IMAGE"
    docker compose down
    docker tag "$PREV_IMAGE" docs-app:latest
    docker compose up -d
    
    log_info "Rollback complete!"
}

# -----------------------------------------------------------------------------
# Status
# -----------------------------------------------------------------------------
status() {
    log_step "Application Status"
    cd "$DOCKER_DIR"
    
    echo ""
    docker compose ps
    echo ""
    
    # Health check
    if curl -sf http://localhost/up > /dev/null 2>&1; then
        log_info "Health: ✓ Healthy"
    else
        log_error "Health: ✗ Unhealthy"
    fi
    
    # Supervisor status
    echo ""
    log_step "Supervisor Processes:"
    docker compose exec -T app supervisorctl status 2>/dev/null || log_warn "Could not get supervisor status"
}

# -----------------------------------------------------------------------------
# Main
# -----------------------------------------------------------------------------
main() {
    case "${1:-deploy}" in
        preflight)
            preflight_checks
            ;;
        build)
            preflight_checks
            build
            ;;
        deploy)
            preflight_checks
            build
            deploy
            ;;
        rollback)
            rollback
            ;;
        status)
            status
            ;;
        *)
            echo "Usage: $0 {preflight|build|deploy|rollback|status}"
            echo ""
            echo "Commands:"
            echo "  preflight  Run pre-deployment checks"
            echo "  build      Build Docker images"
            echo "  deploy     Full deployment (build + start)"
            echo "  rollback   Rollback to previous version"
            echo "  status     Show application status"
            exit 1
            ;;
    esac
}

main "$@"
