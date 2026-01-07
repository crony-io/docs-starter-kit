---
title: Deployment
description: Deploy Docs Starter Kit to production
seo_title: Deployment Guide - Docs Starter Kit
status: published
---

# Deployment

Deploy your documentation site to production.

## Pre-Deployment Checklist

Before deploying, ensure:

- [ ] All tests pass
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Assets built for production
- [ ] SSL certificate ready
- [ ] Backup strategy in place

## Environment Configuration

### Production .env

```env
APP_NAME="My Documentation"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://docs.example.com

# Generate a unique key
APP_KEY=base64:your-production-key

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=docs_production
DB_USERNAME=docs_user
DB_PASSWORD=secure-password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=redis-password

```

### Security Settings

```env
# Force HTTPS
FORCE_HTTPS=true

# Secure cookies
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Rate limiting
RATE_LIMIT_API=60
```

## Build Process

### 1. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
```

### 2. Build Assets

```bash
npm run build
```

### 3. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 4. Run Migrations

```bash
php artisan migrate --force
```

## Server Requirements

### Minimum Specifications

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 1 core | 2+ cores |
| RAM | 1 GB | 2+ GB |
| Storage | 10 GB | 20+ GB |
| PHP | 8.4+ | 8.4+ |

### Required PHP Extensions

- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- cURL
- Redis (optional)

## Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name docs.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name docs.example.com;
    root /var/www/docs/public;

    ssl_certificate /etc/letsencrypt/live/docs.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/docs.example.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## Queue Worker

### Supervisor Configuration

```ini
[program:docs-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/docs/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/docs/storage/logs/worker.log
stopwaitsecs=3600
```

### Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start docs-queue:*
```

## Scheduler

Add to crontab:

```bash
* * * * * cd /var/www/docs && php artisan schedule:run >> /dev/null 2>&1
```

## SSL Certificate

### Let's Encrypt (Certbot)

```bash
sudo certbot --nginx -d docs.example.com
```

### Auto-Renewal

```bash
sudo certbot renew --dry-run
```

## Deployment Methods

### Git-Based Deployment

```bash
cd /var/www/docs
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
sudo supervisorctl restart docs-queue:*
```

### Zero-Downtime Deployment

Use tools like:
- **Envoyer** (Laravel official)
- **Deployer** (PHP deployment tool)
- **GitHub Actions** with SSH

### Docker Deployment

```dockerfile
FROM php:8.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan optimize

EXPOSE 9000
CMD ["php-fpm"]
```

## Monitoring

### Health Checks

Create a health endpoint:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::store()->get('health') !== null || Cache::store()->put('health', true, 10),
    ]);
});
```

### Error Tracking

Configure Sentry or similar:

```env
SENTRY_LARAVEL_DSN=https://your-sentry-dsn
```

### Performance Monitoring

- **Laravel Telescope** (development)
- **New Relic** / **DataDog** (production)

## Backup Strategy

### Database Backups

```bash
# Daily backup
mysqldump -u user -p docs_production > backup-$(date +%Y%m%d).sql

# With compression
mysqldump -u user -p docs_production | gzip > backup-$(date +%Y%m%d).sql.gz
```

### File Backups

```bash
# Storage directory
tar -czf storage-$(date +%Y%m%d).tar.gz storage/app/public
```

### Automated Backups

Use `spatie/laravel-backup`:

```bash
composer require spatie/laravel-backup
php artisan backup:run
```

## Rollback Procedure

If deployment fails:

1. Revert code:
   ```bash
   git checkout previous-commit
   ```

2. Rollback migrations:
   ```bash
   php artisan migrate:rollback
   ```

3. Clear caches:
   ```bash
   php artisan optimize:clear
   ```

4. Restart services:
   ```bash
   sudo supervisorctl restart all
   ```
