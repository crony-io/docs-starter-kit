---
title: Introduction
description: Welcome to Docs Starter Kit - an open-source documentation platform
seo_title: Introduction - Docs Starter Kit
status: published
---

# Introduction

Welcome to **Docs Starter Kit** - an open-source documentation platform built with Laravel, Vue.js, and TypeScript using Inertia.js.

## What is Docs Starter Kit?

Docs Starter Kit is a modern, feature-rich documentation platform that you can clone and customize for your own projects. It provides everything you need to create beautiful, functional documentation websites.

### Key Features

- **Dual Content Mode**: Choose between Git-based synchronization or Database CMS
- **Beautiful UI**: Modern, responsive design with dark mode support
- **Rich Editor**: TipTap-powered WYSIWYG editor for content creation
- **Hierarchical Structure**: Organize docs with navigation tabs, groups, and documents
- **Feedback System**: Collect user feedback with customizable forms
- **LLM Ready**: Auto-generated `llms.txt` files for AI consumption
- **SEO Optimized**: Built-in meta tags, sitemaps, and structured data
- **Version History**: Track changes and restore previous versions

## Why Inertia.js?

Docs Starter Kit uses Inertia.js to combine the best of both worlds:

- **No API needed**: Direct communication between Laravel controllers and Vue components
- **SPA experience**: Client-side navigation without full page reloads
- **Simple state management**: Props from Laravel replace complex state stores
- **SEO friendly**: Server-side rendering support for documentation pages
- **Type-safe**: Full TypeScript support from backend to frontend
- **Form handling**: Built-in form helpers with validation error handling

## Tech Stack

### Frontend
- Vue 3 with Composition API
- TypeScript for type safety
- Tailwind CSS for styling
- TipTap for rich text editing
- Vite as build tool

### Backend
- Laravel 12
- PHP 8.4+
- MySQL/PostgreSQL/SQLite
- Redis for caching (optional)
- Laravel Scout for search
- Laravel Fortify for authentication

## Content Management Modes

You choose your content mode during initial setup. This choice is permanent unless you reset the database.

### Git Mode
Perfect for developers who prefer writing documentation in their favorite editor and managing content through Git workflows.

- Sync from GitHub repositories
- Webhook support for instant updates
- Version control through Git history
- "Edit on GitHub" links

### CMS Mode
Ideal for teams who prefer a visual editor and don't need Git-based workflows.

- Visual WYSIWYG editor
- Drag-and-drop organization
- File manager with uploads
- Version history in database

> **Note**: See [Content Modes](/docs/documentation/configuration/content-modes) for detailed comparison and setup instructions.

## Next Steps

Ready to get started? Head to the [Installation](/docs/documentation/getting-started/installation) guide to set up your documentation site.
