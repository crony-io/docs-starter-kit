---
title: Troubleshooting
description: Common Git sync issues and solutions
seo_title: Git Sync Troubleshooting - Docs Starter Kit
order: 4
status: published
---

# Git Sync Troubleshooting

Solutions for common Git synchronization issues.

## Connection Issues

### "Authentication Required" Error

**Symptoms**: Sync fails with authentication error

**Causes**:
- Missing or invalid access token
- Token doesn't have required permissions
- Token has expired

**Solutions**:

1. Generate a new Personal Access Token:
   - Go to GitHub Settings > Developer Settings > Personal Access Tokens
   - Generate new token (classic)
   - Select `repo` scope for private repos
   - Copy token immediately

2. Update token in admin panel:
   - Go to Git Sync settings
   - Enter new access token
   - Test connection

3. Verify token permissions:
   ```bash
   curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.github.com/repos/owner/repo
   ```

### "Repository Not Found" Error

**Symptoms**: Test connection fails, repository not found

**Causes**:
- Incorrect repository URL
- Repository is private and no token provided
- Repository was deleted or renamed

**Solutions**:

1. Verify repository URL format:
   ```
   https://github.com/username/repository-name
   ```

2. Check repository exists:
   - Visit the URL in your browser
   - Ensure you have access

3. For private repos:
   - Add access token with `repo` scope
   - Verify token owner has repo access

### Connection Timeout

**Symptoms**: Sync hangs or times out

**Causes**:
- Network issues
- GitHub API rate limiting
- Large repository

**Solutions**:

1. Check network connectivity:
   ```bash
   curl -I https://api.github.com
   ```

2. Check rate limit status:
   ```bash
   curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.github.com/rate_limit
   ```

3. For large repos, increase timeout in config:
   ```php
   // config/docs.php
   'sync_timeout' => 600, // 10 minutes
   ```

## Sync Issues

### Pages Not Appearing

**Symptoms**: Sync succeeds but pages don't show up

**Causes**:
- Files not in `docs/` directory
- Invalid frontmatter format
- Status set to `draft`
- Markdown parsing errors

**Solutions**:

1. Verify file location:
   ```
   docs/section/page.md  ✓ Correct
   section/page.md       ✗ Wrong
   ```

2. Check frontmatter format:
   ```markdown
   ---
   title: Page Title
   status: published    # Must be 'published'
   ---
   ```

3. Check sync details in admin panel for errors

4. Test markdown locally:
   ```bash
   php artisan tinker
   >>> app(\App\Services\MarkdownParser::class)->parse(file_get_contents('path/to/file.md'), 'test.md')
   ```

### Wrong Page Order

**Symptoms**: Pages appear in wrong order

**Causes**:
- Missing `order` in frontmatter
- Missing or incorrect `_meta.json`

**Solutions**:

1. Add `order` to frontmatter:
   ```markdown
   ---
   title: Page Title
   order: 1
   ---
   ```

2. Create `_meta.json` with explicit ordering:
   ```json
   {
     "title": "Section",
     "order": 1,
     "items": {
       "first-page": { "order": 1 },
       "second-page": { "order": 2 }
     }
   }
   ```

### Duplicate Pages

**Symptoms**: Same page appears multiple times

**Causes**:
- Page exists in both Git and CMS
- Changed file path without cleanup

**Solutions**:

1. Force full re-sync:
   ```bash
   php artisan docs:sync --force
   ```

2. Manually clean orphaned pages:
   ```bash
   php artisan tinker
   >>> App\Models\Page::where('source', 'git')->whereNull('git_path')->delete()
   ```

### Content Not Updating

**Symptoms**: Changes in repo not reflected on site

**Causes**:
- Sync not triggered
- Caching issues
- Same commit already synced

**Solutions**:

1. Check if sync was triggered:
   - Review Git Sync history in admin
   - Check for webhook delivery in GitHub

2. Clear cache:
   ```bash
   php artisan cache:clear
   ```

3. Force sync even if same commit:
   ```bash
   php artisan docs:sync --force
   ```

## Queue Issues

### Syncs Stuck "In Progress"

**Symptoms**: Sync shows in_progress indefinitely

**Causes**:
- Queue worker not running
- Job failed without updating status
- Worker crashed during sync

**Solutions**:

1. Check queue worker:
   ```bash
   php artisan queue:work --once
   ```

2. Reset stuck syncs:
   ```bash
   php artisan tinker
   >>> App\Models\GitSync::where('sync_status', 'in_progress')
         ->where('created_at', '<', now()->subHours(1))
         ->update(['sync_status' => 'failed', 'error_message' => 'Timed out'])
   ```

3. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```

### Jobs Failing Silently

**Symptoms**: No sync history, no errors

**Causes**:
- Queue connection misconfigured
- Jobs going to wrong queue

**Solutions**:

1. Verify queue configuration:
   ```env
   QUEUE_CONNECTION=redis  # or database, sync
   ```

2. Monitor queue in real-time:
   ```bash
   php artisan queue:work --verbose
   ```

3. Use sync queue for debugging:
   ```env
   QUEUE_CONNECTION=sync
   ```

## CLI Commands

### Test Repository Connection

```bash
php artisan docs:test-repo
```

### Manual Sync

```bash
# Normal sync
php artisan docs:sync

# Force full re-sync
php artisan docs:sync --force
```

### Check Sync Status

```bash
php artisan tinker
>>> App\Models\GitSync::latest()->first()
```

### View Sync History

```bash
php artisan tinker
>>> App\Models\GitSync::latest()->take(10)->get(['id', 'commit_hash', 'sync_status', 'created_at'])
```

### Rollback to Previous Sync

```bash
php artisan docs:rollback {sync_id}
```

## Getting Help

If issues persist:

1. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Review sync details in admin panel

3. Enable debug mode temporarily:
   ```env
   APP_DEBUG=true
   ```

4. Open an issue on GitHub with:
   - Error messages
   - Sync history
   - Repository structure (anonymized)
