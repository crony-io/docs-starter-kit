---
title: LLM Files
description: Generate AI-friendly documentation files
seo_title: LLM File Generation - Docs Starter Kit
status: published
---

# LLM Files

Generate `llms.txt` files for AI assistants to consume your documentation.

## What are LLM Files?

LLM (Large Language Model) files are plain text versions of your documentation optimized for AI consumption. They help AI assistants like ChatGPT, Claude, and GitHub Copilot understand your product.

## Generated Files

Docs Starter Kit generates two files:

### llms.txt

A navigation file listing all documentation pages:

```
# My Documentation

> Official documentation

## Navigation

- [Introduction](/docs/introduction): Welcome to our product
- [Installation](/docs/installation): How to install
- [Configuration](/docs/configuration): Setup options
...

## Last Updated

2024-01-15T10:30:00Z
```

**Location**: `/public/llms.txt`

**Use case**: AI assistants can fetch this to understand documentation structure.

### llms-full.txt

Complete documentation content in a single file:

```
# My Documentation - Complete Documentation

Generated: 2024-01-15T10:30:00Z

---

## Introduction

Welcome to our product...

---

## Installation

Follow these steps...

---
```

**Location**: `/public/llms-full.txt`

**Use case**: AI assistants can ingest full documentation for deep context.

## Enabling LLM Generation

### Via Settings

1. Go to **Settings > Advanced**
2. Enable **LLM.txt Generation**
3. Configure options:
   - Include drafts (default: no)
   - Maximum tokens (default: 100,000)
4. Save changes

## Manual Generation

### Via Admin Panel

1. Go to **Settings > Advanced**
2. Click **Regenerate LLM Files**
3. Wait for generation to complete

### Via Command Line

```bash
php artisan docs:generate-llm
```

Output:
```
Generating LLM files...
✓ LLM files generated successfully
  - public/llms.txt
  - public/llms-full.txt
```

## Automatic Generation

LLM files are automatically regenerated when:
- Documentation pages are created/updated/deleted
- Git sync completes successfully
- Manual regeneration is triggered

### Scheduled Regeneration

Files are also regenerated daily via scheduler:

```php
// app/Console/Kernel.php
$schedule->command('docs:generate-llm')->daily();
```

## File Format

### Structure

```
# {Site Name} - Complete Documentation

Generated: {ISO 8601 timestamp}

---

## {Page Title}

{Page content as plain text}

---
```

### Content Processing

- HTML tags stripped
- Markdown converted to plain text
- Code blocks preserved
- Images referenced by alt text
- Links converted to inline format

## Customization

### Include/Exclude Pages

Control which pages appear in LLM files:

```markdown
---
title: Internal Notes
llm_include: false
---
```

### Custom Ordering

Pages appear in navigation order. Adjust page order to prioritize important content.

### Adding Context

Add a preamble to LLM files in settings:

```
This documentation covers the Acme Widget API.
For support, email support@acme.com.
```

## robots.txt

Consider adding LLM files to robots.txt for AI crawlers:

```
# robots.txt
User-agent: *
Allow: /llms.txt
Allow: /llms-full.txt
```

Or to block:

```
User-agent: GPTBot
Disallow: /llms.txt
Disallow: /llms-full.txt
```

