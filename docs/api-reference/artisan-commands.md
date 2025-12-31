---
title: Artisan Commands
description: Available artisan commands for documentation management
seo_title: Artisan Commands - Docs Starter Kit
order: 3
status: published
---

# Artisan Commands

Custom artisan commands for managing documentation.

## Documentation Commands

### docs:sync

Synchronize documentation from Git repository.

```bash
php artisan docs:sync [--force]
```

**Options:**
- `--force`: Force full re-sync, even if already synced

**Example:**

```bash
# Normal sync (skips if already synced to latest commit)
php artisan docs:sync

# Force full re-sync
php artisan docs:sync --force
```

**Output:**

```
Starting Git sync...
✓ Sync completed successfully!
+---------------+--------------------------------+
| Attribute     | Value                          |
+---------------+--------------------------------+
| Commit        | abc1234                        |
| Author        | John Doe                       |
| Message       | Update installation docs       |
| Files Changed | 5                              |
+---------------+--------------------------------+
```

**Requirements:**
- System must be in Git mode
- Git repository must be configured
- Queue worker running (for async) or sync queue driver

---

### docs:test-repo

Test connection to configured Git repository.

```bash
php artisan docs:test-repo
```

**Example:**

```bash
php artisan docs:test-repo
```

**Success output:**

```
Testing repository connection...
✓ Successfully connected to repository
```

**Failure output:**

```
Testing repository connection...
✗ Failed to connect to repository
```

**Use cases:**
- Verify repository URL is correct
- Check access token permissions
- Debug connection issues

---

### docs:rollback

Rollback documentation to a previous sync state.

```bash
php artisan docs:rollback {sync_id}
```

**Arguments:**
- `sync_id`: ID of the sync to rollback to

**Example:**

```bash
# List recent syncs to find ID
php artisan tinker
>>> App\Models\GitSync::latest()->take(10)->get(['id', 'commit_hash', 'sync_status'])

# Rollback to sync ID 5
php artisan docs:rollback 5
```

**Output:**

```
Rollback to commit abc1234def5678?
 Do you want to continue? (yes/no) [no]:
 > yes

✓ Rollback completed successfully
```

**Notes:**
- Only successful syncs can be rolled back to
- Requires confirmation
- Pages updated after the sync point are removed

---

### docs:generate-llm

Generate LLM documentation files.

```bash
php artisan docs:generate-llm
```

**Example:**

```bash
php artisan docs:generate-llm
```

**Output:**

```
Generating LLM files...
✓ LLM files generated successfully
  - public/llms.txt
  - public/llms-full.txt
```

**Generated files:**
- `public/llms.txt`: Navigation and page list
- `public/llms-full.txt`: Complete documentation content

---

## Standard Laravel Commands

### Useful for Development

```bash
# Clear all caches
php artisan optimize:clear

# Cache configuration for production
php artisan optimize

# Run database migrations
php artisan migrate

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Start development server
php artisan serve

# Start queue worker
php artisan queue:work

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Tinker Examples

```bash
php artisan tinker
```

```php
// Check system configuration
>>> App\Models\SystemConfig::instance()

// List all pages
>>> App\Models\Page::count()

// Get navigation tabs
>>> App\Models\Page::navigationTabs()->get(['id', 'title'])

// Get recent syncs
>>> App\Models\GitSync::latest()->take(5)->get()

// Test markdown parsing
>>> app(App\Services\MarkdownParser::class)->parse("---\ntitle: Test\n---\n# Hello", "test.md")

// Manually trigger sync
>>> app(App\Services\GitSyncService::class)->sync()
```

## Scheduling

Commands scheduled in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Git sync (if enabled)
    if (SystemConfig::isGitMode()) {
        $frequency = SystemConfig::gitSyncFrequency();
        $schedule->command('docs:sync')
            ->everyMinutes($frequency)
            ->withoutOverlapping();
    }
    
    // Daily LLM file generation
    $schedule->command('docs:generate-llm')->daily();
}
```

Ensure scheduler is running:

```bash
# Add to crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# Test scheduler locally
php artisan schedule:work
```

## Creating Custom Commands

### Generate Command

```bash
php artisan make:command DocsCustomCommand
```

### Command Template

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DocsCustomCommand extends Command
{
    protected $signature = 'docs:custom {argument} {--option=default}';
    
    protected $description = 'Description of custom command';

    public function handle(): int
    {
        $argument = $this->argument('argument');
        $option = $this->option('option');
        
        $this->info('Starting custom command...');
        
        try {
            // Your logic here
            
            $this->info('✓ Command completed successfully');
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
```

### Interactive Commands

```php
public function handle(): int
{
    // Ask for input
    $name = $this->ask('What is your name?');
    
    // Confirm action
    if (!$this->confirm('Do you want to proceed?')) {
        $this->info('Cancelled');
        return self::SUCCESS;
    }
    
    // Show progress
    $this->withProgressBar($items, function ($item) {
        // Process item
    });
    
    // Display table
    $this->table(
        ['Column 1', 'Column 2'],
        [
            ['Value 1', 'Value 2'],
            ['Value 3', 'Value 4'],
        ]
    );
    
    return self::SUCCESS;
}
```
