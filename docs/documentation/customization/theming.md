---
title: Theming
description: Customize colors, dark mode, and visual appearance
seo_title: Theme Customization - Docs Starter Kit
order: 1
status: published
---

# Theming

Customize the visual appearance of your documentation site through the admin panel.

## Accessing Theme Settings

Navigate to **Settings > Theme** in the admin panel to access all theme customization options.

## Color Configuration

### Primary Color

The main accent color used throughout your site:
- Navigation highlights
- Links and buttons
- Focus states
- Active elements

**Default**: `#3B82F6` (Blue)

### Secondary Color

Supporting color for secondary elements:
- Secondary buttons
- Hover states
- Badges and tags

**Default**: `#6366F1` (Indigo)

### Accent Color

Used for callouts, warnings, and special highlights:
- Alert boxes
- Important notices
- Call-to-action elements

**Default**: `#F59E0B` (Amber)

### Background Color

Main background color for your site.

**Default**: `#FFFFFF` (White)

### Text Color

Primary text color for content.

**Default**: `#1F2937` (Gray 800)

## Dark Mode

Docs Starter Kit supports three dark mode configurations:

### System (Default)

Follows the user's operating system preference:

```
User's OS is dark → Dark mode
User's OS is light → Light mode
```

### Always Light

Forces light mode regardless of system preference.

### Always Dark

Forces dark mode regardless of system preference.

## Custom CSS

For advanced customization, inject custom CSS through the admin panel:

```css
/* Example: Custom code block styling */
pre code {
  font-size: 14px;
  line-height: 1.6;
}

/* Example: Custom link styling */
.prose a {
  text-decoration: underline;
  text-underline-offset: 2px;
}

/* Example: Custom callout */
.callout-custom {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem;
  border-radius: 0.5rem;
}
```

### CSS Variables

The theme uses CSS variables that you can override:

```css
:root {
  --color-primary: #3B82F6;
  --color-secondary: #6366F1;
  --color-accent: #F59E0B;
  --color-background: #FFFFFF;
  --color-text: #1F2937;
  --color-border: #E5E7EB;
  --color-muted: #6B7280;
}

.dark {
  --color-background: #111827;
  --color-text: #F9FAFB;
  --color-border: #374151;
  --color-muted: #9CA3AF;
}
```

## Color Contrast

When choosing colors, ensure sufficient contrast for accessibility:

- **Normal text**: Minimum 4.5:1 contrast ratio
- **Large text**: Minimum 3:1 contrast ratio
- **UI components**: Minimum 3:1 contrast ratio

Use tools like [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/) to verify.

## Brand Colors

Common brand color configurations:

### Professional Blue

```
Primary: #2563EB
Secondary: #3B82F6
Accent: #F59E0B
```

### Modern Purple

```
Primary: #7C3AED
Secondary: #8B5CF6
Accent: #EC4899
```

### Clean Green

```
Primary: #059669
Secondary: #10B981
Accent: #F59E0B
```

### Corporate Gray

```
Primary: #374151
Secondary: #4B5563
Accent: #3B82F6
```

## Applying Changes

Theme changes are applied immediately and affect:

1. Public documentation pages
2. Admin panel interface
3. Login and authentication pages

Changes are stored in the database and cached for performance.

## Reset to Defaults

To reset theme settings to defaults:

1. Go to **Settings > Theme**
2. Click **Reset to Defaults**
3. Confirm the action

This will restore all color and dark mode settings to their original values.
