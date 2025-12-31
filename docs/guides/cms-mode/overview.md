---
title: CMS Mode Overview
description: Complete guide to using database CMS for documentation
seo_title: CMS Mode Overview - Docs Starter Kit
order: 1
status: published
---

# CMS Mode Overview

CMS Mode provides a full-featured admin panel for managing documentation without Git or code.

## What is CMS Mode?

In CMS Mode, all documentation content is stored in your database and managed through a visual admin interface. This is the default mode and ideal for teams who prefer:

- Visual editing over writing markdown
- Quick content updates without deployments
- Non-technical contributors
- Centralized content management

## Key Features

### Visual Editor

The TipTap-powered editor provides:

- Rich text formatting (bold, italic, headings)
- Code blocks with syntax highlighting
- Tables and lists
- Image embedding
- Link management
- Real-time preview

### Page Management

- Create navigation tabs, groups, and documents
- Drag-and-drop reordering
- Duplicate pages with one click
- Draft/publish workflow
- Version history and restore

### File Manager

- Upload images and documents
- Organize files in folders
- Embed media in documentation
- Bulk operations

### Settings & Customization

- Theme colors and fonts
- Layout configuration
- Branding and logos
- SEO settings

## Getting Started

### 1. Access Admin Panel

Navigate to `/dashboard` and log in with your admin credentials.

### 2. Navigate to Pages

Click **Pages** in the sidebar to view all documentation.

### 3. Create Your First Page

1. Click **Create Page**
2. Choose page type:
   - **Navigation**: Top-level tab
   - **Group**: Sidebar section
   - **Document**: Content page
3. Fill in details and content
4. Click **Create Page**

### 4. Organize Content

Use the tree view to:
- See full hierarchy
- Drag pages to reorder
- Expand/collapse sections

## Page Hierarchy

CMS Mode uses a three-level hierarchy:

```
Navigation Tab (e.g., "Documentation")
└── Group (e.g., "Getting Started")
    └── Document (e.g., "Introduction")
```

### Navigation Tabs

- Appear in top navigation
- Can be set as default landing tab
- Have custom icons
- Cannot have content (container only)

### Groups

- Appear in sidebar
- Organize related documents
- Can be expanded/collapsed
- Support nested groups (limited depth)

### Documents

- Actual content pages
- Support rich text and markdown
- Have SEO metadata
- Track version history

## Workflow

### Creating Content

1. Plan your structure (tabs, groups, documents)
2. Create navigation tabs first
3. Add groups under tabs
4. Create documents within groups
5. Write and format content
6. Set SEO metadata
7. Publish when ready

### Editing Content

1. Navigate to the page
2. Click **Edit**
3. Make changes in editor
4. Changes auto-save as drafts
5. Click **Save** to publish

### Managing Versions

Each save creates a version:
- View version history
- Compare changes
- Restore previous versions
- Track who made changes

## Best Practices

### Structure

- Keep navigation tabs to 4-6 maximum
- Group related content logically
- Use descriptive titles
- Maintain consistent depth

### Content

- Write clear, scannable content
- Use headings for structure
- Include code examples
- Add relevant images

### SEO

- Set custom page titles
- Write meta descriptions
- Use descriptive URLs (slugs)

## Comparison with Git Mode

| Feature | CMS Mode | Git Mode |
|---------|----------|----------|
| Visual Editor | ✅ | ❌ |
| Instant Updates | ✅ | Via webhook |
| Version Control | Database | Git |
| File Uploads | ✅ | Read-only |
| PR Workflow | ❌ | ✅ |
| Offline Editing | ❌ | ✅ |

## Next Steps

- [Learn to create pages](/docs/guides/cms-mode/creating-pages)
- [Master the editor](/docs/guides/cms-mode/editor-guide)
- [Manage media files](/docs/guides/cms-mode/media-management)
