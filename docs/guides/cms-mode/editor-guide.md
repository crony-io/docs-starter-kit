---
title: Editor Guide
description: Master the rich text editor for documentation
seo_title: Editor Guide - Docs Starter Kit
order: 3
status: published
---

# Editor Guide

Master the TipTap-powered rich text editor for creating documentation.

## Editor Overview

The editor provides a WYSIWYG (What You See Is What You Get) experience with:

- Toolbar for common formatting
- Keyboard shortcuts
- Real-time preview
- Markdown shortcuts
- Code block support

## Text Formatting

### Basic Formatting

| Format | Toolbar | Shortcut |
|--------|---------|----------|
| Bold | **B** | `Ctrl+B` |
| Italic | *I* | `Ctrl+I` |
| Strikethrough | ~~S~~ | `Ctrl+Shift+X` |
| Code (inline) | `</>` | `Ctrl+E` |

### Headings

| Level | Markdown | Shortcut |
|-------|----------|----------|
| Heading 1 | `# ` | `Ctrl+Alt+1` |
| Heading 2 | `## ` | `Ctrl+Alt+2` |
| Heading 3 | `### ` | `Ctrl+Alt+3` |
| Heading 4 | `#### ` | `Ctrl+Alt+4` |

Type `#` followed by space at the start of a line to create headings.

## Lists

### Bullet Lists

Create with toolbar or type `- ` or `* ` at line start.

```
- First item
- Second item
  - Nested item
- Third item
```

### Numbered Lists

Create with toolbar or type `1. ` at line start.

```
1. First step
2. Second step
3. Third step
```

### Task Lists

Type `[ ] ` at line start:

```
[ ] Unchecked task
[x] Completed task
```

## Code

### Inline Code

Wrap text with backticks or use `Ctrl+E`:

```
Use the `console.log()` function.
```

### Code Blocks

Create with toolbar or type ``` followed by language:

````
```javascript
function hello() {
  console.log("Hello, World!");
}
```
````

**Supported Languages**:
- javascript, typescript, jsx, tsx
- php, python, ruby, go, rust
- html, css, scss, json, yaml
- bash, shell, sql
- markdown, plaintext

### Syntax Highlighting

Code blocks automatically highlight based on language:

```php
<?php

namespace App\Services;

class Example
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }
}
```

## Links

### Creating Links

1. Select text
2. Click link icon or `Ctrl+K`
3. Enter URL
4. Press Enter

### Link Types

- **External**: `https://example.com`
- **Internal**: `/docs/page-slug`
- **Anchor**: `#section-id`
- **Email**: `mailto:email@example.com`

## Images

### Inserting Images

1. Click image icon in toolbar
2. Select from file manager or upload
3. Add alt text for accessibility

### Image Options

- Alt text (required for accessibility)
- Caption (optional)
- Alignment (left, center, right)
- Size (auto, small, medium, large, full)

## Tables

### Creating Tables

1. Click table icon
2. Select dimensions (rows × columns)
3. Fill in cells

### Table Editing

- Add/remove rows and columns
- Merge cells (select multiple, right-click)
- Resize columns by dragging

### Example

| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| Data | Data | Data |
| Data | Data | Data |

## Blockquotes

Create with toolbar or type `> ` at line start:

```
> This is a blockquote.
> It can span multiple lines.
```

Renders as:

> This is a blockquote.
> It can span multiple lines.

## Horizontal Rules

Create a divider with `---` on its own line:

```
Content above

---

Content below
```

## Keyboard Shortcuts

### Navigation

| Action | Shortcut |
|--------|----------|
| Save | `Ctrl+S` |
| Undo | `Ctrl+Z` |
| Redo | `Ctrl+Shift+Z` |
| Select All | `Ctrl+A` |

### Formatting

| Action | Shortcut |
|--------|----------|
| Bold | `Ctrl+B` |
| Italic | `Ctrl+I` |
| Underline | `Ctrl+U` |
| Strikethrough | `Ctrl+Shift+X` |
| Inline Code | `Ctrl+E` |
| Link | `Ctrl+K` |

### Structure

| Action | Shortcut |
|--------|----------|
| Heading 1 | `Ctrl+Alt+1` |
| Heading 2 | `Ctrl+Alt+2` |
| Heading 3 | `Ctrl+Alt+3` |
| Bullet List | `Ctrl+Shift+8` |
| Numbered List | `Ctrl+Shift+7` |
| Blockquote | `Ctrl+Shift+B` |
| Code Block | `Ctrl+Alt+C` |

## Markdown Mode

You can also write in Markdown directly. The editor supports:

- Headings with `#`
- Bold with `**text**`
- Italic with `*text*`
- Lists with `-` or `1.`
- Links with `[text](url)`
- Images with `![alt](url)`
- Code with backticks

## Tips

### Writing

- Use headings to structure content
- Keep paragraphs short and scannable
- Include code examples where helpful
- Add images to illustrate concepts

### Formatting

- Don't overuse bold/italic
- Use consistent heading levels
- Prefer bullet lists for unordered items
- Use tables for structured data

### Accessibility

- Add alt text to all images
- Use descriptive link text (not "click here")
- Maintain heading hierarchy (don't skip levels)
- Ensure sufficient color contrast
