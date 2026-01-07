---
title: Installation
description: Step-by-step guide to install Docs Starter Kit
seo_title: Installation Guide - Docs Starter Kit
status: published
---

# Installation

Follow these steps to install Docs Starter Kit on your local machine or server.

## Requirements

Before you begin, ensure you have the following installed:

- **PHP 8.4+** (check with `php -v`)
- **Composer** 2.x
- **Node.js** 18.x or higher
- **npm** or **pnpm**
- **MySQL 8.0**, **PostgreSQL 14+**, or **SQLite**
- **Redis** (optional, for caching and queues)

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/crony-io/docs-starter-kit.git
cd docs-starter-kit
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Configure Environment

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure Database

Edit your `.env` file and configure your database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=docs_starter_kit
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### SQLite Alternative

For quick local development, you can use SQLite:

```env
DB_CONNECTION=sqlite
# DB_DATABASE will default to database/database.sqlite
```

Create the SQLite file:

```bash
touch database/database.sqlite
```

### 6. Run Migrations

You have two options:

#### Option A: With Seeders (Recommended for trying out)

```bash
php artisan migrate --seed
```

This creates:
- Default admin user (`admin@example.com` / `password`)
- System configuration (CMS Mode pre-configured)
- Example documentation pages
- Default settings

You'll go directly to the login page.

#### Option B: Fresh Start (Recommended for production)

```bash
php artisan migrate
```

This only creates database tables. You'll be redirected to the **Setup Wizard** to:
- Create your admin account
- Choose content mode (CMS or Git)
- Configure initial settings

### 7. Build Frontend Assets

For development:

```bash
npm run dev
```

For production:

```bash
npm run build
```

### 8. Start the Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` to see your documentation site!

## Default Credentials

If you ran with seeders (`migrate --seed`), log in with:

| Field | Value |
|-------|-------|
| Email | `admin@example.com` |
| Password | `password` |

> **Important**: Change these credentials immediately in production!

If you ran without seeders, the Setup Wizard will prompt you to create your own admin account.

## Queue Worker (Optional)

For background job processing (Git sync, LLM file generation):

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor.

## Troubleshooting

### Database Connection Error

If you see "SQLSTATE[HY000] [2002] Connection refused":

1. Ensure your database server is running
2. Verify credentials in `.env`
3. Check if the database exists

### Permission Errors

If you encounter permission errors on Linux/macOS:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Asset Compilation Fails

If `npm run build` fails:

```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

## Next Steps

Now that you have Docs Starter Kit installed:

1. [Complete the Quick Start guide](/docs/documentation/getting-started/quick-start)
2. [Configure your content mode](/docs/documentation/configuration/content-modes)
3. [Customize your theme](/docs/documentation/customization/theming)
