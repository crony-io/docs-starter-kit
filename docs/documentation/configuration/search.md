---
title: Search
description: Configure full-text search for your documentation
seo_title: Search Configuration - Docs Starter Kit
status: published
---

# Search Configuration

Enable powerful full-text search across your documentation using Laravel Scout.

## Overview

Docs Starter Kit includes built-in search functionality powered by Laravel Scout. Search works across:

- Page titles
- Page content
- SEO descriptions
- Slugs

## Search Providers

### Database Driver (Default)

The simplest option, using your existing database:

```env
SCOUT_DRIVER=database
```

**Pros:**
- No additional services required
- Works with SQLite, MySQL, PostgreSQL
- Zero configuration

**Cons:**
- Less performant for large documentation sets
- Basic matching capabilities

### Meilisearch

A fast, open-source search engine:

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-master-key
```

**Installation:**

```bash
# Docker
docker run -d -p 7700:7700 getmeili/meilisearch:latest

# Or via package manager
# See https://docs.meilisearch.com/learn/getting_started/installation.html
```

**Pros:**
- Very fast search results
- Typo tolerance
- Faceted search
- Self-hosted option

### Algolia

A managed search-as-a-service:

```env
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your-app-id
ALGOLIA_SECRET=your-secret
```

**Pros:**
- Fully managed service
- Excellent performance
- Advanced features
- No infrastructure to maintain

**Cons:**
- Paid service (free tier available)
- Data hosted externally

## Admin Configuration

Search can be configured via **Settings > Advanced**:

| Setting | Description |
|---------|-------------|
| Search Enabled | Toggle search functionality |
| Search Provider | Choose: local, meilisearch, algolia |

## Indexing

### Automatic Indexing

Pages are automatically indexed when:

- Created or updated
- Published
- Content changes

### Manual Indexing

Rebuild the search index:

```bash
# Import all searchable models
php artisan scout:import "App\Models\Page"

# Flush and reimport
php artisan scout:flush "App\Models\Page"
php artisan scout:import "App\Models\Page"
```

## Searchable Content

The Page model defines what's searchable:

```php
public function toSearchableArray(): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'slug' => $this->slug,
        'content' => strip_tags($this->content ?? ''),
        'seo_description' => $this->seo_description,
        'type' => $this->type,
        'full_path' => $this->getFullPath(),
    ];
}
```

### Search Conditions

Only published documents are searchable:

```php
public function shouldBeSearchable(): bool
{
    return $this->isPublished() && $this->isDocument();
}
```

## Search API

### Endpoint

```
GET /search?q={query}
```

### Rate Limiting

Search is rate-limited to 30 requests per minute per IP.

### Response Format

```json
{
  "results": [
    {
      "id": 1,
      "title": "Installation Guide",
      "slug": "installation",
      "path": "documentation/getting-started/installation",
      "excerpt": "...matching content excerpt...",
      "type": "document"
    }
  ],
  "query": "install"
}
```

## Frontend Integration

### Search Dialog

The search dialog is accessible via:

- Click the search icon in navigation
- Keyboard shortcut: `Ctrl+K` or `Cmd+K`

### Features

- Real-time search results
- Keyboard navigation
- Result highlighting
- Quick navigation to pages

## Performance Tips

1. **Use Meilisearch for large sites**: Database search slows down with thousands of pages
2. **Limit indexed content**: Strip HTML and limit content length
3. **Queue indexing**: For bulk imports, use queued indexing
4. **Cache results**: Consider caching popular queries

## Troubleshooting

### No Search Results

1. Check if search is enabled in settings
2. Verify pages are published
3. Rebuild the search index:
   ```bash
   php artisan scout:import "App\Models\Page"
   ```

### Slow Search

1. Consider switching to Meilisearch or Algolia
2. Check database indexes
3. Limit content being indexed

### Meilisearch Connection Failed

1. Verify Meilisearch is running
2. Check host and port configuration
3. Verify API key if set
