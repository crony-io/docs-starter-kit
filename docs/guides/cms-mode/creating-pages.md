---
title: Creating Pages
description: How to create and organize documentation pages
seo_title: Creating Pages - Docs Starter Kit
status: published
---

# Creating Pages

Learn how to create and organize documentation pages in CMS Mode.

## Page Types

### Navigation Tabs

Top-level containers that appear in the header navigation.

**Use for**: Major documentation sections

**Examples**: Documentation, Guides, API Reference, Changelog

**Properties**:
- Title and slug
- Icon (from Lucide icons)
- Order (display position)
- Default flag (landing page)

### Groups

Sidebar sections that organize documents.

**Use for**: Related document collections

**Examples**: Getting Started, Configuration, Tutorials

**Properties**:
- Title and slug
- Parent (must be navigation or another group)
- Icon
- Order
- Expanded state (open by default)

### Documents

The actual content pages.

**Use for**: Documentation content

**Examples**: Introduction, Installation, API Endpoints

**Properties**:
- Title and slug
- Parent (navigation or group)
- Content (rich text/markdown)
- SEO metadata
- Status (draft/published)
- Order

## Creating a Navigation Tab

1. Go to **Pages** in admin
2. Click **Create Page**
3. Select type: **Navigation**
4. Fill in:
   - **Title**: Display name (e.g., "Documentation")
   - **Slug**: URL segment (auto-generated)
   - **Icon**: Choose from icon picker
   - **Order**: Display order (1, 2, 3...)
   - **Default**: Check if this is the landing tab
5. Click **Create Page**

## Creating a Group

1. Go to **Pages** in admin
2. Click **Create Page**
3. Select type: **Group**
4. Fill in:
   - **Title**: Section name (e.g., "Getting Started")
   - **Parent**: Select the navigation tab
   - **Icon**: Optional section icon
   - **Order**: Position within parent
   - **Expanded**: Whether open by default
5. Click **Create Page**

## Creating a Document

1. Go to **Pages** in admin
2. Click **Create Page**
3. Select type: **Document**
4. Fill in:
   - **Title**: Page title
   - **Parent**: Select group or navigation
   - **Content**: Write your documentation
   - **Status**: Draft or Published
5. Add SEO metadata:
   - **SEO Title**: Custom title for search engines
   - **SEO Description**: Meta description
6. Click **Create Page**

## Organizing Pages

### Reordering

1. Go to **Pages** with tree view
2. Drag pages to new positions
3. Drop to reorder within same parent
4. Changes save automatically

### Moving Pages

1. Edit the page
2. Change the **Parent** field
3. Save changes

### Duplicating Pages

1. Find the page in list view
2. Click menu (three dots)
3. Select **Duplicate**
4. Edit the copy as needed

## Page Settings

### Basic Information

| Field | Description |
|-------|-------------|
| Title | Display name in navigation and headings |
| Slug | URL-friendly identifier |
| Parent | Where this page belongs in hierarchy |
| Order | Numeric position (lower = first) |
| Status | Draft (hidden) or Published (visible) |

### For Navigation Tabs

| Field | Description |
|-------|-------------|
| Icon | Lucide icon name |
| Is Default | Set as landing page |

### For Groups

| Field | Description |
|-------|-------------|
| Icon | Lucide icon name |
| Is Expanded | Open by default in sidebar |

### For Documents

| Field | Description |
|-------|-------------|
| Content | Page content (rich text) |
| SEO Title | Custom search engine title |
| SEO Description | Meta description |

## URL Structure

Pages create URLs based on their hierarchy:

```
/docs/{navigation}/{group}/{document}
```

**Examples**:
- `/docs/documentation` - Navigation tab
- `/docs/documentation/getting-started` - Group
- `/docs/documentation/getting-started/installation` - Document

## Status Workflow

### Draft

- Not visible on public site
- Can be edited and previewed
- Use for work in progress

### Published

- Visible on public site
- Appears in navigation
- Indexed by search engines

### Archived

- Hidden from public and admin lists
- Preserved in database
- Can be restored if needed

## Tips

### Naming

- Use clear, descriptive titles
- Keep slugs short but meaningful
- Avoid special characters in slugs

### Structure

- Start with navigation tabs
- Then create groups
- Finally add documents
- Limit depth to 3 levels

### Organization

- Group related content together
- Use consistent naming patterns
- Order pages logically (intro first)

## Common Patterns

### Product Documentation

```
Documentation (nav)
├── Getting Started (group)
│   ├── Introduction
│   ├── Installation
│   └── Quick Start
├── Core Concepts (group)
│   ├── Architecture
│   └── Terminology
└── Configuration (group)
    ├── Basic Setup
    └── Advanced Options
```

### API Reference

```
API Reference (nav)
├── Authentication (group)
│   └── API Keys
├── Endpoints (group)
│   ├── Users
│   ├── Products
│   └── Orders
└── Webhooks (group)
    └── Event Types
```
