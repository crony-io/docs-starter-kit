---
title: Environment Variables
description: Complete reference for all environment variables
seo_title: Environment Variables - Docs Starter Kit
order: 1
status: published
---

# Environment Variables

Configure your Docs Starter Kit installation using environment variables in the `.env` file.

## Application Settings

```env
APP_NAME="Docs Starter Kit"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://docs.example.com
APP_KEY=base64:your-generated-key
```

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Your site name | Docs Starter Kit |
| `APP_ENV` | Environment (local, staging, production) | local |
| `APP_DEBUG` | Enable debug mode (disable in production!) | true |
| `APP_URL` | Full URL to your site | http://localhost |
| `APP_KEY` | Encryption key (generate with `php artisan key:generate`) | - |

## Database Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=docs_starter_kit
DB_USERNAME=root
DB_PASSWORD=secret
```

### Supported Databases

- **MySQL 8.0+**: `DB_CONNECTION=mysql`
- **PostgreSQL 14+**: `DB_CONNECTION=pgsql`
- **SQLite**: `DB_CONNECTION=sqlite`

## Cache and Session

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

For simpler setups without Redis:

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## Content Mode Settings

```env
# Content management mode: 'cms' or 'git'
DOCS_CONTENT_MODE=cms
```

### Git Mode Settings

When using Git mode, configure these additional variables:

```env
DOCS_GIT_ENABLED=true
DOCS_GIT_SYNC_FREQUENCY=15
DOCS_MAX_REPO_SIZE=500
```

| Variable | Description | Default |
|----------|-------------|---------|
| `DOCS_GIT_ENABLED` | Enable Git sync feature | true |
| `DOCS_GIT_SYNC_FREQUENCY` | Minutes between auto-syncs | 15 |
| `DOCS_MAX_REPO_SIZE` | Maximum repo size in MB | 500 |

> **Note**: Git repository URL, branch, and access token are configured through the admin panel and stored encrypted in the database.

## Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="docs@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## File Storage

```env
FILESYSTEM_DISK=local
```

For cloud storage (AWS S3):

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

## Search Configuration

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-master-key
```

For local search (no external service):

```env
SCOUT_DRIVER=collection
```

## Security Settings

```env
# Session lifetime in minutes
SESSION_LIFETIME=120

# HTTPS only cookies
SESSION_SECURE_COOKIE=true

# Rate limiting
RATE_LIMIT_API=60
```

## Production Checklist

Before deploying to production, ensure:

1. `APP_ENV=production`
2. `APP_DEBUG=false`
3. `APP_KEY` is set and secure
4. Database credentials are correct
5. `APP_URL` matches your domain
6. HTTPS is configured
7. Cache and session drivers are set (preferably Redis)
8. Mail is configured for notifications

## Example Production Configuration

```env
APP_NAME="My Documentation"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://docs.mycompany.com

DB_CONNECTION=mysql
DB_HOST=db.mycompany.com
DB_DATABASE=docs_production
DB_USERNAME=docs_user
DB_PASSWORD=super-secure-password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis.mycompany.com
REDIS_PASSWORD=redis-password

DOCS_CONTENT_MODE=git
DOCS_GIT_ENABLED=true
```
