---
title: Database
description: Database configuration and optimization
seo_title: Database Configuration - Docs Starter Kit
order: 3
status: published
---

# Database Configuration

Docs Starter Kit supports multiple database systems. This guide covers configuration and optimization for each.

## Supported Databases

- **MySQL 8.0+** (recommended for production)
- **PostgreSQL 14+** (great for production)
- **SQLite** (perfect for development)

## MySQL Configuration

### Basic Setup

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=docs_starter_kit
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Production Optimization

For production MySQL, add these settings to your MySQL configuration:

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 200
```

### Character Set

Ensure your database uses `utf8mb4` for full Unicode support:

```sql
CREATE DATABASE docs_starter_kit
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

## PostgreSQL Configuration

### Basic Setup

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=docs_starter_kit
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### Search Configuration

PostgreSQL has excellent full-text search. To use it:

```env
SCOUT_DRIVER=database
```

## SQLite Configuration

### Basic Setup

SQLite is the simplest option for local development:

```env
DB_CONNECTION=sqlite
```

Create the database file:

```bash
touch database/database.sqlite
```

### Limitations

- Not recommended for production with high traffic
- No concurrent write support
- Limited full-text search capabilities

## Running Migrations

After configuring your database:

```bash
# Run all migrations
php artisan migrate

# Run migrations with seeders
php artisan migrate --seed

# Fresh install (drops all tables)
php artisan migrate:fresh --seed
```

## Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `users` | Admin users |
| `pages` | Documentation pages (navigation, groups, documents) |
| `page_versions` | Version history for pages |
| `system_config` | System configuration (content mode, Git settings) |
| `settings` | Site settings (theme, branding, etc.) |
| `git_syncs` | Git synchronization history |
| `media` | Uploaded media files |
| `feedback_forms` | Feedback form configurations |
| `feedback_responses` | User feedback submissions |

### Pages Table Structure

The `pages` table uses a hierarchical structure:

```
Navigation (parent_id: null)
└── Group (parent_id: navigation.id)
    └── Document (parent_id: group.id)
```

Key columns:
- `type`: 'navigation', 'group', or 'document'
- `source`: 'cms' or 'git'
- `status`: 'draft', 'published', or 'archived'
- `order`: Display order within parent

## Backup Strategies

### MySQL Dump

```bash
mysqldump -u root -p docs_starter_kit > backup.sql
```

### PostgreSQL Dump

```bash
pg_dump -U postgres docs_starter_kit > backup.sql
```

### Laravel Backup Package

For automated backups, consider using `spatie/laravel-backup`:

```bash
composer require spatie/laravel-backup
php artisan backup:run
```

## Performance Tips

### 1. Enable Query Caching

```env
CACHE_DRIVER=redis
```

### 2. Use Database Indexing

The migrations include indexes on frequently queried columns:
- `pages.type`
- `pages.status`
- `pages.parent_id`
- `pages.slug`

### 3. Eager Loading

The application uses eager loading to prevent N+1 queries:

```php
Page::with(['parent', 'children'])->get();
```

### 4. Connection Pooling

For high-traffic sites, use connection pooling:

```env
DB_POOL_MIN=5
DB_POOL_MAX=20
```

## Troubleshooting

### Connection Refused

1. Verify database server is running
2. Check host and port settings
3. Ensure firewall allows connections

### Access Denied

1. Verify username and password
2. Check user has proper permissions
3. For MySQL: `GRANT ALL ON docs_starter_kit.* TO 'user'@'localhost';`

### Migration Errors

```bash
# Clear cached config
php artisan config:clear

# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback
php artisan migrate
```
