# Docs Starter Kit

An **open-source starter kit** for building modern documentation websites with Vue.js, Laravel, and TypeScript.

## Disclaimer

**Work in Progress:** This project is still under active development. Features may change.

**AI-Assisted Development:** This project was developed with the assistance of AI tools.

---

## Why Docs Starter Kit?

Skip months of development and launch your documentation site in minutes. Whether you prefer writing in your favorite code editor with Git workflows or using a visual CMS, this starter kit has you covered.

**Built with:** Laravel 12, Vue 3, Inertia.js, TypeScript, Tailwind CSS, shadcn/ui

---

## Key Features

### Content Mode
Choose your workflow during the guided setup wizard:
- **Git Mode:** Sync from GitHub with webhooks, differential updates, rollback support, and "Edit on GitHub" links, YAML frontmatter support for SEO metadata
- **CMS Mode:** TipTap WYSIWYG or raw Markdown editor, drag-and-drop page tree, version history, and draft/published workflow

### Page Editor
- Toggle between TipTap WYSIWYG and raw Markdown modes
- Integrated media browser for inserting images, videos, and files
- Tables, task lists, and strikethrough extensions
- Syntax highlighting with 190+ languages

### Documentation UI
- Responsive design with light/dark/system theme
- Navigation tabs, collapsible groups, and nested pages
- Auto-generated table of contents (left or right position)
- Code blocks with copy button and line numbers
- Copy page content and download as TXT
- Smooth anchor scrolling and internal link handling

### Full Customization
- **Theme:** Primary/secondary/accent colors, dark mode, custom CSS injection
- **Typography:** Google Fonts for headings, body, and code; font sizes and line heights
- **Layout:** Sidebar/content width, TOC position, breadcrumbs, footer
- **Branding:** Site name, tagline, light/dark logos, favicon, social links (Twitter, GitHub, Discord, LinkedIn)

### Page Tree with Drag-and-Drop Reordering
- Hierarchical page tree with visual drag-and-drop
- Smart type validation (navigation, groups, documents)
- Auto-expand on hover during drag
- Prevents invalid moves (e.g., can't nest navigation)

### Full-Text Search
- Laravel Scout integration
- Search result highlighting with context excerpts

### Feedback System
- "Was this helpful?" widget with thumbs up/down
- Drag-and-drop form field builder
- Multiple field types: text, textarea, radio, checkbox, rating, email
- Configurable required fields
- Analytics dashboard with helpfulness scores
- Export to CSV/JSON

### Media Manager
- Folder organization with nested structure
- Drag-and-drop file uploads
- Automatic thumbnail and preview generation
- Bulk selection and deletion
- Move files between folders

### SEO & Structured Data
- Dynamic XML sitemap
- Per-page SEO title and description
- Canonical URLs
- OpenGraph and Twitter Card meta tags
- JSON-LD structured data (TechArticle schema)
- Meta robots configuration

### Server-Side Rendering (SSR)
- Full SSR support via Inertia.js for faster initial page loads
- Better SEO with server-rendered content
- Clustered SSR server for performance

### LLM Ready
- Auto-generated `llms-full.txt` (complete page content)
- Copy and download txt on all documents pages
- Configurable token limits
- Regenerates on content changes

### Security
- Two-Factor Authentication with QR code and recovery codes
- Content Security Policy (CSP) with nonce support
- Cloudflare Turnstile CAPTCHA on login
- Rate limiting on sensitive endpoints
- GitHub webhook signature verification (HMAC SHA-256)
- Encrypted storage for tokens and secrets
- Single session per user

### Privacy & Compliance
- Activity logging with IP geolocation
- Configurable log retention and cleanup
- IP anonymization command for GDPR compliance
- Sensitive data filtering (passwords, tokens never logged)

### Developer Experience
- CSP-compliant dynamic styles directive (`v-csp-style`)
- Type-safe routes with Laravel Wayfinder
- Fully typed TypeScript frontend

### Deployment Ready
- Web-cron for shared hosting (scheduler + queue worker)
- Works without shell access or traditional cron
- Server compatibility checker
- Async execution when available

---

## Setup Wizard

On first visit, a guided wizard walks you through:
1. Admin account creation
2. Content mode selection (Git or CMS)
3. Git repository configuration (if Git mode)
4. Site branding and settings
5. Review and launch

---

## License

MIT License - see [LICENSE](LICENSE) file for details.
