---
title: Git Mode Setup
description: Complete guide to setting up Git-based documentation sync
seo_title: Git Mode Setup Guide - Docs Starter Kit
status: published
---

# Git Mode Setup

This guide walks you through setting up Git-based documentation synchronization with GitHub.

## Prerequisites

Before you begin, ensure you have:

- A GitHub repository for your documentation
- Admin access to your Docs Starter Kit installation
- (Optional) A GitHub Personal Access Token for private repositories

## Step 1: Prepare Your Repository

Create or use an existing GitHub repository with the expected structure:

```
your-docs-repo/
├── docs/
│   ├── assets/           # Reserved folder for images/files
│   │   └── images/
│   ├── section-name/     # Navigation tab
│   │   ├── _meta.json
│   │   └── page.md
│   └── another-section/  # Navigation tab
│       └── page.md
└── docs-config.json
```

> **Note**: The `assets` folder inside `docs/` is reserved for static files and won't appear as navigation.

See [Repository Structure](/docs/guides/git-mode/repository-structure) for detailed format.

## Step 2: Configure Git Mode

### Via Setup Wizard

If this is a fresh installation:

1. Navigate to `/admin/setup`
2. Select **Git-Based** documentation
3. Enter your repository details:
   - **Repository URL**: `https://github.com/username/repo`
   - **Branch**: `main` (or your documentation branch)
   - **Access Token**: Required for private repositories
4. Click **Continue**


## Step 3: Generate Access Token (Private Repos)

For private repositories, create a GitHub Personal Access Token:

1. Go to [GitHub Settings > Developer Settings > Personal Access Tokens](https://github.com/settings/tokens)
2. Click **Generate new token (classic)**
3. Give it a descriptive name: "Docs Starter Kit Sync"
4. Select scopes:
   - `repo` - Full control (for private repos)
   - OR `public_repo` - Only public repos
5. Click **Generate token**
6. Copy the token immediately (you won't see it again)
7. Paste it in the admin panel configuration

> **Security Note**: The access token is encrypted in the database and never exposed in the UI after saving.

## Step 4: Test Connection

Before syncing, test your repository connection:

### Via Admin Panel

1. Go to **Git Sync**
2. Click **Test Connection**
3. Verify you see "Successfully connected to repository"


## Step 5: Initial Sync

Trigger the first synchronization:

### Via Admin Panel

1. Go to **Git Sync** in the admin sidebar
2. Click **Sync Now**
3. Wait for the sync to complete (runs as background job)
4. Check the sync history for status

The initial sync is automatically triggered when you complete the setup wizard with Git mode selected.

> **Note**: Syncs run as background jobs on the `high-priority` queue. Ensure your queue worker is running.

## Step 6: Configure Auto-Sync

### Scheduled Sync

Auto-sync runs at configurable intervals:

1. Go to **Git Sync** configuration
2. Set **Sync Frequency** (default: 15 minutes)
3. Save changes

Ensure your Laravel scheduler is running:

```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Webhook Sync (Recommended)

For instant updates on push, set up a webhook. See [Webhooks Guide](/docs/guides/git-mode/webhooks).

## Step 7: Verify Documentation

After syncing:

1. Visit `/docs` on your site
2. Verify all pages appear correctly
3. Check navigation structure matches your repository
4. Test internal links between pages

## Queue Configuration

Git sync runs as a background job. Ensure your queue worker is running:

```bash
# Development
php artisan queue:work

# Production (use Supervisor)
php artisan queue:work --queue=high-priority,default --tries=3
```

## Next Steps

- [Set up webhooks for instant sync](/docs/guides/git-mode/webhooks)
- [Learn the repository structure](/docs/guides/git-mode/repository-structure)
- [Troubleshoot sync issues](/docs/guides/git-mode/troubleshooting)
