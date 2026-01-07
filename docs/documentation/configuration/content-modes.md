---
title: Content Modes
description: Understanding CMS Mode and Git Mode
seo_title: Content Modes - Docs Starter Kit
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

CMS Mode is the default. Select it during the initial setup wizard at `/admin/setup`.

> **Note**: Content mode is configured during setup and stored in the database. It cannot be changed via environment variables.

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

Select Git Mode during the initial setup wizard at `/admin/setup`:

1. Choose **Git-Based** documentation
2. Enter your repository details:
   - **Repository URL**: `https://github.com/username/repo`
   - **Branch**: Your documentation branch (e.g., `main`)
   - **Access Token**: Required for private repositories
   - **Webhook Secret**: For secure webhook triggers
3. Complete the setup wizard

After setup, you can update Git settings (but not the mode) in **Git Sync** settings.

### Repository Structure

Your documentation repository should follow this structure:

```
your-docs-repo/
├── docs/
│   ├── assets/           # Reserved folder for images/files
│   │   └── images/
│   ├── section-name/     # Navigation tab
│   │   ├── _meta.json
│   │   ├── page-one.md
│   │   └── page-two.md
│   └── another-section/  # Navigation tab
│       └── page.md
└── docs-config.json
```

> **Note**: The `assets` folder inside `docs/` is reserved for static files and won't appear as navigation.

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

> **Important**: Content mode is set during initial setup and **cannot be changed** through the admin panel. This is by design to prevent data inconsistencies.

### To Switch Modes

Switching content modes requires a fresh database reset:

```bash
php artisan migrate:fresh
```

This will:
- Drop all existing tables
- Re-run all migrations
- Redirect you to the setup wizard where you can choose a different mode

> **Warning**: This deletes all content, users, and settings. Back up any important data before resetting.

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
