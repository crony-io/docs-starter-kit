# Docker Deployment Guide

This guide will help you run the Docs application using Docker. Whether you're new to Docker or experienced, follow the appropriate section for your use case.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Understanding the Setup](#understanding-the-setup)
3. [Development Setup](#development-setup) - For local development with hot reload
4. [Production Setup](#production-setup) - For deploying to a server
5. [Common Commands](#common-commands)
6. [Database Options](#database-options)
7. [Troubleshooting](#troubleshooting)
8. [Maintenance](#maintenance)

---

## Prerequisites

Before starting, make sure you have these installed on your computer:

### Required Software

| Software | Minimum Version | How to Check | Download |
|----------|-----------------|--------------|----------|
| **Docker** | 24.0+ | `docker --version` | [docker.com](https://www.docker.com/products/docker-desktop/) |
| **Docker Compose** | 2.20+ | `docker compose version` | Included with Docker Desktop |

### For Windows Users
- Install **Docker Desktop for Windows**
- Enable WSL 2 backend (recommended) during installation
- After installation, Docker icon appears in system tray

### For Mac Users
- Install **Docker Desktop for Mac**
- Allow Docker in System Preferences > Security & Privacy if prompted

### For Linux Users
```bash
# Install Docker
curl -fsSL https://get.docker.com | sh

# Add your user to docker group (logout/login after)
sudo usermod -aG docker $USER
```

---

## Understanding the Setup

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    APP CONTAINER (docs-app)                 │
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────────┐  │
│  │  Nginx   │  │ PHP-FPM  │  │ Node SSR │  │Queue Worker │  │
│  │  :80     │──│ (socket) │  │  :13714  │  │ (2 workers) │  │
│  └──────────┘  └──────────┘  └──────────┘  └─────────────┘  │
│                                                             │
│  Web Server    PHP Runtime   Server-Side    Background Jobs │
│                              Rendering                      │
└─────────────────────────────────────────────────────────────┘
             │                                   │
             ▼                                   ▼
      ┌─────────────┐                     ┌─────────────┐
      │   MySQL     │                     │    Redis    │
      │ (docs-db)   │                     │(docs-redis) │
      │   :3306     │                     │   :6379     │
      └─────────────┘                     └─────────────┘
        Database                        Cache/Sessions/Queues
```

### Software Versions

| Software | Version |
|----------|---------|
| PHP | 8.5.1-fpm |
| Node.js | 25 |
| Nginx | 1.29.4 |
| MySQL | 9.5.0 |
| PostgreSQL | 18.1 (optional) |
| Redis | 8.4.0 |

---

## Development Setup

Use this setup when you're actively coding. It provides:
- 🔥 **Hot reload** - Changes appear instantly without rebuilding
- 🐛 **Debug access** - Connect to database/Redis from your tools
- 📧 **Email testing** - Catch all emails with Mailpit

### Step 1: Navigate to Docker Directory

Open your terminal and navigate to the docker folder:

```bash
cd docker
```

### Step 2: Create Environment File

Copy the example environment file:

```bash
cp .env.example .env
```

### Step 3: Configure Environment Variables

Open `.env` in your text editor and set these values:

```ini
# Application
APP_NAME="Docs"
APP_ENV=local                              # Keep as 'local' for development
APP_KEY=                                   # Will be generated automatically
APP_DEBUG=true                             # Enable debug mode
APP_URL=http://localhost                   # Your local URL

# Database - Choose secure passwords
DB_PASSWORD=dev_password_123               # Your database password
DB_ROOT_PASSWORD=root_password_123         # MySQL root password

# Redis - Required for sessions/cache
REDIS_PASSWORD=redis_password_123          # Your Redis password
```

> 💡 **Tip**: For development, simple passwords are fine. Use strong passwords for production!

### Step 4: Build and Start Development Environment

Run this single command to build and start everything:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

**What this command does:**
- `-f docker-compose.yml` - Loads the base configuration
- `-f docker-compose.dev.yml` - Adds development-specific settings (hot reload, exposed ports)
- `up` - Creates and starts the containers
- `-d` - Runs in background (detached mode)

### Step 5: Wait for Initialization

The first startup takes a few minutes. Watch the progress:

```bash
docker compose logs -f app
```

You'll see messages like:
```
[INFO] Creating required directories...
[INFO] Waiting for database connection...
[INFO] Database is ready!
[INFO] Running Laravel setup tasks...
[INFO] Running database migrations...
[INFO] Initialization complete! Starting services...
```

Press `Ctrl+C` to exit logs (containers keep running).

### Step 6: Access Your Application

| Service | URL | Description |
|---------|-----|-------------|
| **Application** | http://localhost | Main website |
| **Mailpit** | http://localhost:8025 | Email testing UI |
| **Vite HMR** | http://localhost:5173 | Hot reload (automatic) |

### Development Workflow

Once running, your local files are synced with the container:

1. **Edit code** in your IDE as normal
2. **Frontend changes** (Vue/CSS) appear instantly via hot reload
3. **Backend changes** (PHP) take effect on next request
4. **Run artisan commands** via Docker:
   ```bash
   docker compose exec app php artisan migrate
   docker compose exec app php artisan tinker
   ```

### Stopping Development Environment

```bash
# Stop containers (keeps data)
docker compose down

# Stop and remove all data (fresh start)
docker compose down -v
```

---

## Production Setup

Use this setup for deploying to a server. It provides:
- 🔒 **Security hardened** - No exposed ports except HTTP
- ⚡ **Optimized** - Cached configs, compiled assets
- 🔄 **Auto-restart** - Services restart on failure

### Step 1: Server Requirements

Your server needs:
- Linux (Ubuntu 22.04+ recommended)
- Docker and Docker Compose installed
- At least 1GB RAM, 1 CPU core (2GB/2 cores recommended for faster builds)
- Domain name pointing to server IP

### Step 2: Clone Repository on Server

```bash
git clone <your-repo-url> /var/www/docs
cd /var/www/docs/docker
```

### Step 3: Create Production Environment File

```bash
cp .env.example .env
```

### Step 4: Configure Production Environment

Edit `.env` with secure values:

```ini
# Application
APP_NAME="Docs"
APP_ENV=production                         # IMPORTANT: Set to production
APP_KEY=                                   # Generate below
APP_DEBUG=false                            # IMPORTANT: Disable debug
APP_URL=https://docs.yourdomain.com        # Your actual domain

# Database - USE STRONG PASSWORDS
DB_PASSWORD=Xk9#mP2$vL5nQ8@wR               # Generate: openssl rand -base64 24
DB_ROOT_PASSWORD=Yt7&hN4!cB9xM3#fK           # Generate: openssl rand -base64 24

# Redis - USE STRONG PASSWORD  
REDIS_PASSWORD=Zp6*jW1@sD8yF5!vC             # Generate: openssl rand -base64 24

# Session Security
SESSION_SECURE_COOKIE=true                 # Requires HTTPS

# Mail (configure your SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourmailprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

### Step 5: Generate Application Key

```bash
# Generate a secure APP_KEY
docker run --rm php:8.5-cli php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

Copy the output and paste it as `APP_KEY=` in your `.env` file.

### Step 6: Build Production Image

```bash
docker compose build --no-cache
```

This takes 5-10 minutes. It:
- Installs all PHP dependencies
- Builds frontend assets (Vue, CSS, JS)
- Creates optimized production image

### Step 7: Start Production Services

```bash
docker compose up -d
```

### Step 8: Verify Deployment

Check that everything is running:

```bash
# Check container status
docker compose ps

# Expected output:
# NAME          STATUS                   PORTS
# docs-app      Up (healthy)             0.0.0.0:80->80/tcp
# docs-db       Up (healthy)             3306/tcp
# docs-redis    Up                       6379/tcp
```

Test the health endpoint:

```bash
curl http://localhost/up
# Should return: OK or JSON health status
```

### Step 9: Set Up HTTPS (Recommended)

For production, set up a reverse proxy with SSL. Options:

**Option A: Nginx Reverse Proxy with Let's Encrypt**
```bash
# Install certbot
apt install certbot python3-certbot-nginx

# Get certificate
certbot --nginx -d docs.yourdomain.com
```

**Option B: Cloudflare (easiest)**
- Add domain to Cloudflare
- Enable "Full (strict)" SSL mode
- Create SSL certificate in Cloudflare and add it to your nginx conf
- Traffic is automatically encrypted

### Production Architecture

```
Internet → Cloudflare/Nginx (SSL) → Docker (port 80) → App
                                              ↓
                                    MySQL + Redis (internal only)
```

---

## Common Commands

### Container Management

```bash
# Start all services
docker compose up -d

# Stop all services  
docker compose down

# Restart a specific service
docker compose restart app

# View running containers
docker compose ps

# View resource usage
docker stats
```

### Laravel Artisan Commands

All artisan commands must be run inside the container:

```bash
# Pattern: docker compose exec app php artisan <command>

# Database
docker compose exec app php artisan migrate              # Run migrations
docker compose exec app php artisan migrate:fresh       # Reset database
docker compose exec app php artisan db:seed             # Seed database

# Cache
docker compose exec app php artisan optimize            # Cache everything
docker compose exec app php artisan optimize:clear      # Clear all caches

# Queue
docker compose exec app php artisan queue:work          # Process jobs (manual)
docker compose exec app php artisan queue:retry all     # Retry failed jobs

# Debugging
docker compose exec app php artisan tinker              # Interactive shell
docker compose exec app php artisan route:list          # List all routes
```

### Viewing Logs

```bash
# All container logs
docker compose logs -f

# Specific container
docker compose logs -f app
docker compose logs -f db

# Laravel application log
docker compose exec app tail -f storage/logs/laravel.log

# Last 100 lines
docker compose logs --tail=100 app
```

### Accessing Container Shell

```bash
# Get a shell inside the app container
docker compose exec app sh

# Now you can run commands directly:
# $ php artisan migrate
# $ composer install
# $ exit
```

### Supervisor (Process Manager)

The app container uses Supervisor to manage processes:

```bash
# Check all process status
docker compose exec app supervisorctl status

# Restart queue workers
docker compose exec app supervisorctl restart queue-worker:*

# Restart SSR server
docker compose exec app supervisorctl restart ssr
```

---

## Database Options

### MySQL (Default)

MySQL is configured by default. Connect with:

| Setting | Value |
|---------|-------|
| Host | `db` (from app) or `localhost:3306` (from host in dev) |
| Database | `docs` |
| Username | `docs` |
| Password | Your `DB_PASSWORD` |

### PostgreSQL (Alternative)

To use PostgreSQL instead of MySQL:

**Step 1:** Update your `.env`:
```ini
DB_CONNECTION=pgsql
DB_PORT=5432
```

**Step 2:** Start with PostgreSQL compose file:
```bash
docker compose -f docker-compose.yml -f docker-compose.postgres.yml up -d
```

### Database GUI Tools

In development mode, connect your favorite database tool:

**TablePlus / DBeaver / MySQL Workbench:**
- Host: `localhost`
- Port: `3306` (MySQL) or `5432` (PostgreSQL)
- User: `docs`
- Password: Your `DB_PASSWORD`
- Database: `docs`

---

## Troubleshooting

### Container Won't Start

**Check logs first:**
```bash
docker compose logs app
```

**Common causes:**

1. **Port already in use**
   ```
   Error: port 80 already in use
   ```
   Solution: Stop other services using port 80, or change `APP_PORT` in `.env`

2. **Missing environment variables**
   ```
   Error: APP_KEY not set
   ```
   Solution: Generate and set APP_KEY in `.env`

3. **Database connection failed**
   ```
   SQLSTATE[HY000] [2002] Connection refused
   ```
   Solution: Wait for database to be ready, or check DB_PASSWORD matches

### Database Connection Issues

```bash
# Check if database container is running
docker compose ps db

# Test database connection
docker compose exec db mysqladmin ping -h localhost

# Check database logs
docker compose logs db

# Verify environment variables
docker compose exec app env | grep DB_
```

### Redis Connection Issues

```bash
# Check Redis status
docker compose exec redis redis-cli -a YOUR_REDIS_PASSWORD ping
# Should return: PONG

# Check Redis logs
docker compose logs redis
```

### Permission Errors

If you see "Permission denied" errors:

```bash
# Fix storage permissions
docker compose exec app chown -R www:www storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### SSR Not Working

Server-Side Rendering issues:

```bash
# Check SSR process status
docker compose exec app supervisorctl status ssr

# View SSR logs
docker compose exec app tail -50 /var/log/supervisor/ssr-error.log

# Restart SSR
docker compose exec app supervisorctl restart ssr
```

### Out of Disk Space

```bash
# Remove unused Docker data
docker system prune -a

# Remove unused volumes (⚠️ deletes data!)
docker volume prune
```

### Complete Reset

If nothing works, start fresh:

```bash
# Stop and remove everything
docker compose down -v

# Remove all images
docker compose down --rmi all

# Rebuild from scratch
docker compose build --no-cache
docker compose up -d
```

---

## Maintenance

### Updating the Application

```bash
# 1. Pull latest code
git pull origin main

# 2. Rebuild the image
docker compose build --no-cache

# 3. Restart with new image
docker compose up -d

# 4. Run migrations
docker compose exec app php artisan migrate --force

# 5. Clear caches
docker compose exec app php artisan optimize
```

### Backup

#### Database Backup

```bash
# MySQL
docker compose exec db mysqldump -u root -p"$DB_ROOT_PASSWORD" docs > backup-$(date +%Y%m%d).sql

# PostgreSQL  
docker compose exec db pg_dump -U docs docs > backup-$(date +%Y%m%d).sql

# Compress
gzip backup-*.sql
```

#### Storage Backup

```bash
# Backup uploaded files
docker compose exec app tar -czf /tmp/storage.tar.gz -C /var/www/html storage/app
docker cp $(docker compose ps -q app):/tmp/storage.tar.gz ./storage-backup-$(date +%Y%m%d).tar.gz
```

#### Automated Backups

Use the included backup script:

```bash
./scripts/backup.sh all      # Database + storage
./scripts/backup.sh db       # Database only
./scripts/backup.sh storage  # Storage only
```

### Restore from Backup

```bash
# MySQL
gunzip backup-20240101.sql.gz
docker compose exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" docs < backup-20240101.sql

# PostgreSQL
docker compose exec -T db psql -U docs docs < backup-20240101.sql
```

### Health Monitoring

The application exposes a health check endpoint:

```bash
# Check application health
curl -f http://localhost/up

# Use in monitoring tools (uptime checks)
# Endpoint returns HTTP 200 if healthy, 500+ if unhealthy
```

### Scaling

#### Vertical Scaling (More Resources)

Edit `docker-compose.yml` to adjust memory/CPU limits:

```yaml
deploy:
  resources:
    limits:
      cpus: '2'
      memory: 1G
```

#### Horizontal Scaling (Multiple Instances)

For high traffic, run multiple app containers behind a load balancer:

```bash
docker compose up -d --scale app=3
```

Requires:
- External load balancer (Nginx, HAProxy, or cloud LB)
- Shared Redis for sessions
- Shared database

---

## Quick Reference

### File Structure

```
docker/
├── .env.example          # Environment template
├── .env                  # Your configuration (create this)
├── Dockerfile            # App container build instructions
├── docker-compose.yml    # Production configuration
├── docker-compose.dev.yml    # Development overrides
├── docker-compose.postgres.yml  # PostgreSQL alternative
├── entrypoint.sh         # Container startup script
├── nginx/                # Nginx configuration
├── php/                  # PHP configuration  
├── mysql/                # MySQL configuration
├── supervisor/           # Process manager configuration
└── scripts/              # Helper scripts
```

### Environment Modes

| Mode | Command | Use For |
|------|---------|---------|
| **Production** | `docker compose up -d` | Live servers |
| **Development** | `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d` | Local coding |
| **PostgreSQL** | `docker compose -f docker-compose.yml -f docker-compose.postgres.yml up -d` | PostgreSQL instead of MySQL |

### Ports (Development Mode)

| Port | Service |
|------|---------|
| 80 | Application |
| 3306 | MySQL |
| 5432 | PostgreSQL |
| 6379 | Redis |
| 5173 | Vite HMR |
| 8025 | Mailpit UI |
| 13714 | SSR Server |
