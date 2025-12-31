---
title: API Overview
description: Overview of Docs Starter Kit internal APIs and services
seo_title: API Overview - Docs Starter Kit
order: 1
status: published
---

# API Overview

Docs Starter Kit is built with Inertia.js, which means there's no traditional REST API. Instead, controllers return Inertia responses directly to Vue components.

## Architecture

### Inertia.js Approach

Instead of:
```
Frontend → REST API → Backend → JSON Response
```

Inertia uses:
```
Vue Component → Inertia Request → Laravel Controller → Inertia Response → Vue Component
```

### Benefits

- **No API versioning**: Direct coupling between frontend and backend
- **Type safety**: TypeScript types generated from Laravel
- **Simpler code**: No API serialization layer
- **Built-in features**: CSRF, validation errors, redirects

## Key Services

### GitSyncService

Handles Git repository synchronization:

```php
use App\Services\GitSyncService;

// Inject via constructor
public function __construct(
    private GitSyncService $syncService
) {}

// Trigger sync
$sync = $this->syncService->sync();

// Test connection
$success = $this->syncService->testConnection();

// Rollback to previous sync
$this->syncService->rollback($sync);
```

### MarkdownParser

Parses markdown files with frontmatter:

```php
use App\Services\MarkdownParser;

$parser = new MarkdownParser();

// Parse markdown with frontmatter
$result = $parser->parse($markdownContent, 'path/to/file.md');

// Returns:
// [
//     'title' => 'Page Title',
//     'slug' => 'page-title',
//     'content' => 'Markdown body...',
//     'seo_title' => 'SEO Title',
//     'seo_description' => 'Description',
//     'order' => 1,
//     'status' => 'published',
//     'hierarchy' => ['section', 'page-title'],
//     'git_path' => 'path/to/file.md',
// ]

// Render markdown to HTML
$html = $parser->renderToHtml($markdown);

// Parse _meta.json files
$meta = $parser->parseMetaFile($jsonContent);
```

### ContentImporter

Imports parsed content into the database:

```php
use App\Services\ContentImporter;

$importer = new ContentImporter();

// Import a parsed page
$page = $importer->import($parsedContent, $commitInfo);

// Clean up removed pages
$deleted = $importer->deleteRemovedPages($currentGitPaths);
```

### GitHubApiClient

Interacts with GitHub API:

```php
use App\Services\GitHubApiClient;

$client = new GitHubApiClient(
    repository: 'owner/repo',
    branch: 'main',
    token: 'ghp_xxx'
);

// Test connection
$success = $client->testConnection();

// Get latest commit
$commit = $client->getLatestCommit();

// Get file content
$content = $client->getFileContent('docs/page.md');

// Get directory tree
$tree = $client->getDirectoryTree('docs');

// Get changed files between commits
$changes = $client->getChangedFiles($fromSha, $toSha);
```

## Models

### Page

The main content model:

```php
use App\Models\Page;

// Query scopes
Page::published()->get();
Page::navigationTabs()->get();
Page::groups()->get();
Page::documents()->get();
Page::fromGit()->get();
Page::fromCms()->get();

// Instance methods
$page->isNavigation();
$page->isGroup();
$page->isDocument();
$page->isPublished();
$page->isFromGit();
$page->getFullPath();
$page->getNavigationTab();
$page->createVersion();
$page->publish();
$page->unpublish();
$page->archive();
```

### SystemConfig

Singleton for system configuration:

```php
use App\Models\SystemConfig;

// Get instance (cached)
$config = SystemConfig::instance();

// Static helpers
SystemConfig::isGitMode();
SystemConfig::isCmsMode();
SystemConfig::gitSyncFrequency();
SystemConfig::isSetupCompleted();

// Clear cache after changes
SystemConfig::clearCache();
```

### GitSync

Tracks synchronization history:

```php
use App\Models\GitSync;

// Query scopes
GitSync::successful()->get();
GitSync::failed()->get();
GitSync::inProgress()->get();
GitSync::recent(20)->get();

// Instance methods
$sync->isSuccessful();
$sync->isFailed();
$sync->isInProgress();
$sync->markAsSuccess($filesChanged);
$sync->markAsFailed($errorMessage);
```

## Jobs

### SyncGitRepositoryJob

Background Git synchronization:

```php
use App\Jobs\SyncGitRepositoryJob;

// Dispatch to queue
SyncGitRepositoryJob::dispatch();

// Dispatch to high-priority queue
SyncGitRepositoryJob::dispatch()->onQueue('high-priority');

// Run synchronously
SyncGitRepositoryJob::dispatchSync();
```

### GenerateLLMFilesJob

Generate LLM documentation files:

```php
use App\Jobs\GenerateLLMFilesJob;

// Dispatch to queue
GenerateLLMFilesJob::dispatch();

// Run synchronously
GenerateLLMFilesJob::dispatchSync();
```

## Events

### GitSyncCompleted

Fired after successful sync:

```php
use App\Events\GitSyncCompleted;

// Listen in EventServiceProvider
GitSyncCompleted::class => [
    RegenerateLLMFiles::class,
    UpdateSearchIndex::class,
],
```

### GitSyncFailed

Fired after failed sync:

```php
use App\Events\GitSyncFailed;

// Listen in EventServiceProvider
GitSyncFailed::class => [
    NotifyAdminOfSyncFailure::class,
],
```

## Extending

### Custom Services

Create services in `app/Services/`:

```php
namespace App\Services;

class CustomService
{
    public function __construct(
        private MarkdownParser $parser
    ) {}
    
    public function process(): void
    {
        // Your logic
    }
}
```

### Custom Jobs

Create jobs in `app/Jobs/`:

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomJob implements ShouldQueue
{
    use Queueable;
    
    public function handle(): void
    {
        // Your logic
    }
}
```
