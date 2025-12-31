---
title: Quick Start
description: Get up and running with Docs Starter Kit in minutes
seo_title: Quick Start Guide - Docs Starter Kit
order: 3
status: published
---

# Quick Start

Get your documentation site up and running in just a few minutes.

## 1. Access the Admin Panel

After installation, navigate to `/dashboard` and log in with:

- **Email**: `admin@example.com`
- **Password**: `password`

## 2. Choose Your Content Mode

During first setup, you'll choose how to manage content:

### CMS Mode (Default)

Best for teams who prefer visual editing:

- Use the built-in WYSIWYG editor
- Drag-and-drop page organization
- Upload and manage media files
- No Git knowledge required

### Git Mode

Best for developer documentation:

- Sync from a GitHub repository
- Write in Markdown with your favorite editor
- Use pull requests for content reviews
- Automatic deployment on push

## 3. Understand the Page Hierarchy

Docs Starter Kit uses a three-level hierarchy:

### Navigation Tabs

Top-level categories that appear in the header navigation.

**Examples**: Documentation, Guides, API Reference, Changelog

### Groups

Sidebar sections that organize related documents.

**Examples**: Getting Started, Configuration, Tutorials

### Documents

The actual content pages with your documentation.

**Examples**: Introduction, Installation, Quick Start

## 4. Create Your First Page

### Using CMS Mode

1. Go to **Pages** in the admin sidebar
2. Click **Create Page**
3. Select page type:
   - **Navigation**: For top-level tabs
   - **Group**: For sidebar sections
   - **Document**: For content pages
4. Fill in the details:
   - **Title**: Page name
   - **Slug**: URL-friendly identifier (auto-generated)
   - **Parent**: Where this page belongs
   - **Content**: Your documentation content
5. Set **Status** to "Published"
6. Click **Create Page**

### Using Git Mode

1. Create a markdown file in your docs repository:

```markdown
---
title: My First Page
description: A description for SEO
order: 1
status: published
---

# My First Page

Your content here...
```

2. Commit and push to your configured branch
3. The sync will import your page automatically

## 5. Customize Your Site

### Branding

Go to **Settings > Branding** to:

- Upload your logo
- Set site name and tagline
- Add social media links

### Theme

Go to **Settings > Theme** to:

- Choose primary and accent colors
- Configure dark mode behavior
- Add custom CSS

### Layout

Go to **Settings > Layout** to:

- Adjust sidebar and content width
- Toggle table of contents
- Configure breadcrumbs

## 6. View Your Documentation

Visit `/docs` to see your public documentation site. The first published document will be displayed by default.

## Common Tasks

### Reorder Pages

In the admin Pages view, use drag-and-drop to reorder pages within their parent.

### Duplicate a Page

Click the menu on any page and select "Duplicate" to create a copy.

### Preview Changes

When editing, your changes are visible in real-time in the editor preview panel.

### Publish/Unpublish

Toggle page visibility without deleting by changing the status between Published and Draft.

## Next Steps

- [Configure environment variables](/docs/documentation/configuration/environment-variables)
- [Set up Git synchronization](/docs/guides/git-mode/setup)
- [Customize your theme](/docs/documentation/customization/theming)
