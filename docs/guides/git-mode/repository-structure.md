---
title: Repository Structure
description: How to structure your documentation repository for Git sync
seo_title: Repository Structure - Docs Starter Kit
order: 2
status: published
---

# Repository Structure

Learn how to structure your documentation repository for seamless Git synchronization.

## Overview

Docs Starter Kit expects a specific folder structure in your repository:

```
your-docs-repo/
├── docs/                    # All documentation content
│   ├── navigation-tab/      # Top-level navigation
│   │   ├── _meta.json       # Folder configuration
│   │   ├── group/           # Sidebar group
│   │   │   ├── _meta.json
│   │   │   └── page.md      # Document
│   │   └── standalone.md    # Direct child document
│   └── another-tab/
│       └── ...
├── assets/                  # Media files
│   ├── images/
│   └── downloads/
└── docs-config.json         # Global configuration
```

## Folder Hierarchy

The folder structure maps directly to page types:

| Folder Level | Page Type | Example |
|--------------|-----------|---------|
| `docs/[folder]/` | Navigation | Documentation, Guides |
| `docs/[nav]/[folder]/` | Group | Getting Started, API |
| `docs/[nav]/[group]/[file].md` | Document | introduction.md |

## Markdown Files

### Frontmatter

Every markdown file should include YAML frontmatter:

```markdown
---
title: Page Title
description: Brief description for SEO and previews
seo_title: Custom SEO Title (optional)
order: 1
status: published
---

# Page Title

Your content here...
```

### Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | Page title (falls back to filename) |

### Optional Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `description` | string | - | SEO meta description |
| `seo_title` | string | title | Custom SEO title |
| `order` | number | 0 | Display order in navigation |
| `status` | string | published | `published` or `draft` |

### Content Format

Standard Markdown with extensions:

```markdown
# Heading 1
## Heading 2
### Heading 3

**Bold text** and *italic text*

- Bullet list
- Another item

1. Numbered list
2. Second item

> Blockquote

`inline code`

​```javascript
// Code block with syntax highlighting
function hello() {
  console.log("Hello!");
}
​```

| Table | Header |
|-------|--------|
| Cell  | Cell   |

[Link text](https://example.com)

![Image alt](/assets/images/screenshot.png)
```

## Meta Files

### _meta.json

Configure folder titles, ordering, and icons:

```json
{
  "title": "Getting Started",
  "description": "Learn the basics",
  "order": 1,
  "icon": "rocket",
  "is_expanded": true,
  "items": {
    "introduction": {
      "title": "Introduction",
      "order": 1
    },
    "installation": {
      "title": "Installation Guide",
      "order": 2
    },
    "quick-start": {
      "title": "Quick Start",
      "order": 3
    }
  }
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | Display title for the folder |
| `description` | string | Folder description |
| `order` | number | Display order among siblings |
| `icon` | string | Icon name (Lucide icons) |
| `is_expanded` | boolean | Whether group is expanded by default |
| `items` | object | Override titles/order for child pages |

### Available Icons

Common icon names (from Lucide):

- `book`, `file-text`, `folder`
- `rocket`, `zap`, `star`
- `settings`, `sliders`, `wrench`
- `code`, `terminal`, `git-branch`
- `compass`, `map`, `navigation`
- `users`, `user`, `shield`

## docs-config.json

Global configuration at repository root:

```json
{
  "name": "My Documentation",
  "version": "1.0.0",
  "language": "en",
  "theme": {
    "primary_color": "#3B82F6",
    "dark_mode": true
  },
  "features": {
    "search": true,
    "feedback": true,
    "table_of_contents": true
  },
  "navigation": {
    "logo": "/assets/images/logo.png",
    "links": [
      {
        "text": "GitHub",
        "url": "https://github.com/org/repo",
        "external": true
      }
    ]
  },
  "seo": {
    "title": "My Documentation",
    "description": "Official documentation",
    "keywords": "docs, guide, api"
  }
}
```

## Assets Directory

Store media files in the `assets/` folder:

```
assets/
├── images/
│   ├── logo.png
│   ├── screenshot-1.png
│   └── diagrams/
│       └── architecture.svg
├── downloads/
│   └── example.zip
└── videos/
    └── demo.mp4
```

Reference in markdown:

```markdown
![Screenshot](/assets/images/screenshot-1.png)

[Download Example](/assets/downloads/example.zip)
```

## Example Structure

Complete example for a typical documentation site:

```
my-docs/
├── docs/
│   ├── documentation/
│   │   ├── _meta.json
│   │   ├── getting-started/
│   │   │   ├── _meta.json
│   │   │   ├── introduction.md
│   │   │   ├── installation.md
│   │   │   └── quick-start.md
│   │   └── configuration/
│   │       ├── _meta.json
│   │       ├── environment.md
│   │       └── database.md
│   ├── guides/
│   │   ├── _meta.json
│   │   └── tutorials/
│   │       ├── _meta.json
│   │       └── first-steps.md
│   ├── api-reference/
│   │   ├── _meta.json
│   │   └── endpoints/
│   │       ├── _meta.json
│   │       ├── users.md
│   │       └── posts.md
│   └── changelog/
│       ├── _meta.json
│       └── v1-0-0.md
├── assets/
│   └── images/
│       └── logo.png
└── docs-config.json
```

## Best Practices

1. **Use kebab-case** for folder and file names
2. **Keep hierarchy shallow** - max 3-4 levels deep
3. **Include _meta.json** in every folder for proper ordering
4. **Set explicit order** values to control navigation order
5. **Use descriptive filenames** that become URL slugs
6. **Optimize images** before adding to assets
7. **Commit frequently** for better sync history
