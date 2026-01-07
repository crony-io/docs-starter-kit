---
title: Layout
description: Configure sidebar, content width, and page layout
seo_title: Layout Configuration - Docs Starter Kit
status: published
---

# Layout Configuration

Customize the layout of your documentation site including sidebar, content area, and navigation.

## Accessing Layout Settings

Navigate to **Settings > Layout** in the admin panel.

## Sidebar Configuration

### Sidebar Width

Control the width of the navigation sidebar.

**Default**: 280px

**Recommended range**: 240px - 320px

### Navigation Style

Choose how navigation is displayed:

- **Sidebar** (default): Traditional left sidebar navigation
- **Top Nav**: Horizontal navigation in header
- **Both**: Sidebar with supplementary top navigation

## Content Area

### Content Width

Maximum width of the main content area.

**Default**: 900px

**Recommended range**: 720px - 1024px

Wider content is better for:
- API documentation with wide code blocks
- Tables with many columns
- Image-heavy documentation

Narrower content is better for:
- Long-form prose
- Tutorial content
- Mobile-first design

## Table of Contents

### Show Table of Contents

Enable or disable the table of contents sidebar.

**Default**: Enabled

### TOC Position

Where to display the table of contents:

- **Right** (default): Fixed on the right side
- **Left**: Alongside main navigation
- **Floating**: Dropdown button on mobile

The table of contents is automatically generated from headings (h2, h3) in your content.

## Breadcrumbs

### Show Breadcrumbs

Display navigation breadcrumbs above page content.

**Default**: Enabled

Breadcrumbs show the page hierarchy:

```
Documentation > Getting Started > Installation
```

## Footer

### Show Footer

Display the footer section on documentation pages.

**Default**: Enabled

### Footer Text

Custom text displayed in the footer.

**Default**: `© 2024 Docs Starter Kit. All rights reserved.`

Supports basic HTML:

```html
© 2026 <a href="https://example.com">My Company</a>. 
Built with <a href="https://github.com/crony-io/docs-starter-kit">Docs Starter Kit</a>.
```

## Responsive Behavior

The layout automatically adapts to different screen sizes:

### Desktop (1024px+)

- Full sidebar visible
- Table of contents on right
- Wide content area

### Tablet (768px - 1023px)

- Collapsible sidebar
- Hidden table of contents
- Full-width content

### Mobile (< 768px)

- Hamburger menu for navigation
- Stacked layout
- Touch-friendly spacing

## Layout Examples

### Documentation Site

```
Sidebar Width: 280px
Content Width: 800px
Show TOC: Yes
TOC Position: Right
Show Breadcrumbs: Yes
```

### API Reference

```
Sidebar Width: 240px
Content Width: 1024px
Show TOC: Yes
TOC Position: Right
Show Breadcrumbs: Yes
```

### Tutorial Site

```
Sidebar Width: 260px
Content Width: 720px
Show TOC: No
TOC Position: -
Show Breadcrumbs: Yes
```

### Minimal Blog Style

```
Sidebar Width: 200px
Content Width: 680px
Show TOC: No
TOC Position: -
Show Breadcrumbs: No
```

## Custom Layout CSS

For advanced layout customization:

```css
/* Wider sidebar */
.sidebar {
  width: 320px;
}

/* Centered content */
.content-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

/* Sticky TOC */
.table-of-contents {
  position: sticky;
  top: 80px;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}

/* Custom footer */
.footer {
  background: linear-gradient(to right, #1f2937, #374151);
  color: white;
  padding: 2rem;
}

/* Hide sidebar on certain pages */
.page-landing .sidebar {
  display: none;
}
```

## Performance Considerations

Layout settings affect page performance:

1. **Sidebar items**: Large navigation trees take longer to render
2. **TOC depth**: Limit heading levels to h2-h3 for better performance
3. **Fixed elements**: Sticky headers/TOC use GPU acceleration
4. **Animations**: Keep transitions under 300ms for smooth UX
