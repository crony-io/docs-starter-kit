---
title: LLM Files
description: Generate AI-friendly documentation files
seo_title: LLM File Generation - Docs Starter Kit
order: 2
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

### Environment Variables

```env
# Enable LLM file generation
LLM_TXT_ENABLED=true

# Include draft pages
LLM_TXT_INCLUDE_DRAFTS=false

# Maximum token count
LLM_TXT_MAX_TOKENS=100000
```

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

## Token Counting

### Why Tokens Matter

LLMs have context limits:
- GPT-4: ~128k tokens
- Claude: ~100k tokens
- GPT-3.5: ~16k tokens

### Token Estimation

Rough estimate: 1 token ≈ 4 characters

The generator estimates tokens and warns if content exceeds limits.

### Handling Large Documentation

If documentation exceeds token limits:

1. **Prioritize**: Include most important pages
2. **Summarize**: Use shorter descriptions
3. **Split**: Create topic-specific LLM files
4. **Reference**: Link to full docs for details

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

## Use Cases

### Customer Support AI

Train support chatbots on your documentation:

1. Fetch `llms-full.txt` periodically
2. Use as context for AI responses
3. Link to specific pages for details

### IDE Integration

Help AI coding assistants understand your API:

1. Reference `llms.txt` in project
2. AI can suggest correct API usage
3. Reduces incorrect suggestions

### Search Enhancement

Improve search with AI understanding:

1. Use LLM file for semantic search
2. Better relevance ranking
3. Natural language queries

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

## Best Practices

1. **Keep content concise**: Shorter docs = more context room
2. **Update regularly**: Stale docs confuse AI
3. **Test with AI**: Verify AI understands your docs
4. **Monitor usage**: Check access logs for LLM file requests
5. **Version appropriately**: Include version info in docs
