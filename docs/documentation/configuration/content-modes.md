---
title: Content Modes
description: Understanding CMS Mode and Git Mode
seo_title: Content Modes - Docs Starter Kit
order: 2
status: published
---

# Content Modes

Docs Starter Kit supports two distinct content management modes. Choose the one that best fits your team's workflow.

## CMS Mode

**Best for**: Non-technical teams, visual editing, quick content updates

In CMS Mode, all content is stored in the database and managed through the admin panel.

### Features

- **Visual Editor**: TipTap-powered WYSIWYG editor
- **Drag-and-Drop**: Reorganize pages easily
- **File Manager**: Upload and manage media
- **Version History**: Track changes in the database
- **Draft/Publish Workflow**: Preview before publishing
- **Auto-Save**: Never lose your work

### How to Enable

CMS Mode is the default. During setup or in your configuration:

```env
DOCS_CONTENT_MODE=cms
```

### Creating Content

1. Navigate to **Pages** in the admin panel
2. Click **Create Page**
3. Use the rich text editor to write content
4. Organize with drag-and-drop
5. Publish when ready

### Limitations

- No Git integration or version control through Git
- No pull request workflow for content reviews
- Backups must be handled manually or through database backups

## Git Mode

**Best for**: Developer documentation, open source projects, version-controlled content

In Git Mode, content is synchronized from a GitHub repository.

### Features

- **Git History**: Full version control through commits
- **PR Workflow**: Review changes through pull requests
- **Familiar Tools**: Write in your favorite editor
- **Webhook Sync**: Instant updates on push
- **"Edit on GitHub"**: Direct links for contributors
- **Branch Selection**: Sync from any branch

### How to Enable

1. Set environment variable:

```env
DOCS_CONTENT_MODE=git
```

2. Configure through admin panel:
   - Repository URL
   - Branch name
   - Access token (for private repos)
   - Webhook secret

### Repository Structure

Your documentation repository should follow this structure:

```
your-docs-repo/
├── docs/
│   ├── section-name/
│   │   ├── _meta.json
│   │   ├── page-one.md
│   │   └── page-two.md
│   └── another-section/
│       └── page.md
├── assets/
│   └── images/
└── docs-config.json
```

### Markdown Format

Each markdown file should include frontmatter:

```markdown
---
title: Page Title
description: SEO description
order: 1
status: published
---

# Page Title

Your content here...
```

### Folder Configuration

Use `_meta.json` files to configure folder titles and ordering:

```json
{
  "title": "Getting Started",
  "order": 1,
  "icon": "rocket",
  "items": {
    "introduction": {
      "title": "Introduction",
      "order": 1
    }
  }
}
```

### Sync Behavior

- **Automatic**: Syncs at configured intervals (default: 15 minutes)
- **Webhook**: Instant sync when you push (recommended)
- **Manual**: Trigger from admin panel or CLI

### Limitations

- No visual editor (content managed in Git)
- File manager shows synced assets only (read-only)
- Structure follows repository organization

## Switching Modes

> **Warning**: Switching modes after content is created may result in data loss. Plan your content mode before creating documentation.

If you need to switch:

1. **CMS to Git**: Export your content to markdown files, set up repository, then switch mode
2. **Git to CMS**: Your Git-synced content will remain, but new edits will be CMS-based

## Comparison Table

| Feature | CMS Mode | Git Mode |
|---------|----------|----------|
| Visual Editor | ✅ | ❌ |
| Markdown Support | ✅ | ✅ |
| Version Control | Database | Git |
| PR Workflow | ❌ | ✅ |
| Instant Updates | ✅ | Via Webhook |
| File Uploads | ✅ | Read-only |
| Offline Editing | ❌ | ✅ |
| "Edit on GitHub" | ❌ | ✅ |

## Recommendations

### Choose CMS Mode if:

- Your team is non-technical
- You need visual editing capabilities
- You want quick, simple content updates
- Git workflows seem complex for your needs

### Choose Git Mode if:

- You're documenting a developer product
- Your team already uses Git
- You want PR-based content reviews
- You prefer writing in VS Code or similar
