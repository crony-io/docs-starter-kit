---
title: Pages API
description: Page model and controller reference
seo_title: Pages API Reference - Docs Starter Kit
order: 2
status: published
---

# Pages API

Reference for the Page model and PageController.

## Page Model

### Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `id` | integer | Primary key |
| `title` | string | Page title |
| `slug` | string | URL-friendly identifier |
| `type` | enum | `navigation`, `group`, `document` |
| `icon` | string | Lucide icon name |
| `content` | text | Page content (markdown) |
| `status` | enum | `draft`, `published`, `archived` |
| `order` | integer | Display order |
| `parent_id` | integer | Parent page ID |
| `is_default` | boolean | Default landing page |
| `is_expanded` | boolean | Sidebar expanded state |
| `seo_title` | string | SEO title override |
| `seo_description` | text | Meta description |
| `source` | enum | `cms`, `git` |
| `git_path` | string | Path in Git repository |
| `git_last_commit` | string | Last commit SHA |
| `git_last_author` | string | Last commit author |
| `updated_at_git` | timestamp | Git update time |
| `created_by` | integer | Author user ID |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update time |

### Relationships

```php
// Parent page
$page->parent;

// Child pages
$page->children;

// Version history
$page->versions;

// Feedback responses
$page->feedbackResponses;

// Author
$page->author;
```

### Query Scopes

```php
// Filter by status
Page::published()->get();
Page::draft()->get();
Page::archived()->get();

// Filter by type
Page::navigationTabs()->get();
Page::groups()->get();
Page::documents()->get();

// Filter by source
Page::fromGit()->get();
Page::fromCms()->get();

// Root level pages
Page::rootLevel()->get();
```

### Instance Methods

```php
// Type checks
$page->isNavigation();  // bool
$page->isGroup();       // bool
$page->isDocument();    // bool

// Status checks
$page->isPublished();   // bool
$page->isDraft();       // bool
$page->isFromGit();     // bool

// Navigation
$page->getNavigationTab();  // ?Page
$page->getFullPath();       // string (e.g., "docs/getting-started/intro")

// Status changes
$page->publish();      // self
$page->unpublish();    // self
$page->archive();      // self

// Versioning
$page->createVersion();  // PageVersion

// Computed attributes
$page->breadcrumbs;           // array
$page->edit_on_github_url;    // ?string
```

## PageController

### Routes

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/admin/pages` | index | List all pages |
| GET | `/admin/pages/create` | create | Create page form |
| POST | `/admin/pages` | store | Store new page |
| GET | `/admin/pages/{page}/edit` | edit | Edit page form |
| PUT | `/admin/pages/{page}` | update | Update page |
| DELETE | `/admin/pages/{page}` | destroy | Delete page |
| POST | `/admin/pages/{page}/duplicate` | duplicate | Duplicate page |
| POST | `/admin/pages/reorder` | reorder | Reorder pages |
| POST | `/admin/pages/{page}/publish` | publish | Publish page |
| POST | `/admin/pages/{page}/unpublish` | unpublish | Unpublish page |
| POST | `/admin/pages/{page}/restore-version/{id}` | restoreVersion | Restore version |

### Index Response

```typescript
interface IndexProps {
  pages: Paginated<Page>;
  treeData: TreeNode[];
  navigationTabs: Page[];
  filters: {
    status?: string;
    search?: string;
    type?: string;
    view?: string;
  };
  statuses: SelectOption[];
  types: SelectOption[];
}
```

### Store Request

```php
// PageStoreRequest validation
[
    'title' => 'required|string|max:255',
    'slug' => 'nullable|string|max:255',
    'type' => 'required|in:navigation,group,document',
    'icon' => 'nullable|string|max:50',
    'content' => 'nullable|string',
    'status' => 'required|in:draft,published,archived',
    'parent_id' => 'nullable|exists:pages,id',
    'is_default' => 'boolean',
    'is_expanded' => 'boolean',
    'seo_title' => 'nullable|string|max:255',
    'seo_description' => 'nullable|string|max:500',
]
```

### Update Request

```php
// PageUpdateRequest validation
[
    'title' => 'required|string|max:255',
    'slug' => 'required|string|max:255',
    'type' => 'required|in:navigation,group,document',
    'icon' => 'nullable|string|max:50',
    'content' => 'nullable|string',
    'status' => 'required|in:draft,published,archived',
    'parent_id' => 'nullable|exists:pages,id',
    'is_default' => 'boolean',
    'is_expanded' => 'boolean',
    'seo_title' => 'nullable|string|max:255',
    'seo_description' => 'nullable|string|max:500',
]
```

### Reorder Request

```php
[
    'pages' => 'required|array',
    'pages.*.id' => 'required|exists:pages,id',
    'pages.*.order' => 'required|integer|min:0',
]
```

## DocsController (Public)

### Routes

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/docs` | index | Documentation home |
| GET | `/docs/{path}` | show | View documentation page |

### Show Response

```typescript
interface ShowProps {
  navigationTabs: NavigationTab[];
  activeNavId: number | null;
  sidebarItems: SidebarItem[];
  currentPage: PageData | null;
  tableOfContents: TOCItem[];
  breadcrumbs: Breadcrumb[];
  feedbackForms: FeedbackForm[];
}

interface PageData {
  id: number;
  title: string;
  slug: string;
  type: string;
  seo_title: string | null;
  seo_description: string | null;
  updated_at: string;
  source: string;
  updated_at_git: string | null;
  git_last_author: string | null;
  content_raw: string | null;
  content: string | null;  // HTML rendered
  edit_on_github_url: string | null;
}
```

## TypeScript Types

### Page Type

```typescript
interface Page {
  id: number;
  title: string;
  slug: string;
  type: 'navigation' | 'group' | 'document';
  icon: string | null;
  content: string | null;
  status: 'draft' | 'published' | 'archived';
  order: number;
  parent_id: number | null;
  is_default: boolean;
  is_expanded: boolean;
  seo_title: string | null;
  seo_description: string | null;
  source: 'cms' | 'git';
  git_path: string | null;
  created_at: string;
  updated_at: string;
  full_path?: string;
  parent?: Page | null;
  children?: Page[];
}
```

### TreeNode Type

```typescript
interface TreeNode {
  id: number;
  title: string;
  slug: string;
  type: string;
  status: string;
  updated_at: string;
  children: TreeNode[];
}
```

### SidebarItem Type

```typescript
interface SidebarItem {
  id: number;
  title: string;
  slug: string;
  type: string;
  icon: string | null;
  path: string;
  isExpanded: boolean;
  children?: SidebarItem[];
}
```
