---
title: Typography
description: Configure fonts and text styling
seo_title: Typography Settings - Docs Starter Kit
order: 2
status: published
---

# Typography

Configure fonts and text styling to match your brand and improve readability.

## Accessing Typography Settings

Navigate to **Settings > Typography** in the admin panel.

## Font Configuration

### Heading Font

The font used for all headings (h1-h6):
- Page titles
- Section headers
- Navigation items

**Default**: Inter

### Body Font

The font used for paragraph text and general content:
- Documentation content
- Descriptions
- UI labels

**Default**: Inter

### Code Font

Monospace font for code blocks and inline code:
- Code snippets
- Terminal commands
- File paths

**Default**: JetBrains Mono

## Available Fonts

Docs Starter Kit integrates with Google Fonts. Popular choices include:

### Sans-Serif (Headings & Body)

- **Inter** - Modern, highly readable
- **Open Sans** - Clean and neutral
- **Roboto** - Google's signature font
- **Lato** - Friendly and approachable
- **Nunito** - Rounded and modern
- **Source Sans Pro** - Adobe's open source font

### Monospace (Code)

- **JetBrains Mono** - Designed for developers
- **Fira Code** - Popular with ligatures
- **Source Code Pro** - Adobe's monospace
- **IBM Plex Mono** - Corporate and clean
- **Roboto Mono** - Matches Roboto family

## Size Configuration

### Base Font Size

The root font size that all other sizes scale from.

**Default**: 16px

**Recommended range**: 14px - 18px

### Heading Scale

The multiplier used to calculate heading sizes:

| Scale | h1 | h2 | h3 | h4 |
|-------|-----|-----|-----|-----|
| 1.125 | 1.8x | 1.6x | 1.4x | 1.25x |
| 1.25 (default) | 2.4x | 1.95x | 1.56x | 1.25x |
| 1.333 | 3.2x | 2.4x | 1.8x | 1.33x |

### Line Height

The spacing between lines of text.

**Default**: 1.6

**Recommendations**:
- Body text: 1.5 - 1.7
- Headings: 1.2 - 1.4
- Code: 1.4 - 1.6

### Paragraph Spacing

Space between paragraphs, measured in `em` units.

**Default**: 1.5em

## Typography Best Practices

### Readability

1. **Line length**: Keep content width between 60-80 characters
2. **Contrast**: Ensure text has sufficient contrast against background
3. **Hierarchy**: Use consistent heading levels
4. **White space**: Don't crowd text; use adequate margins

### Accessibility

1. **Minimum size**: Don't go below 14px for body text
2. **Scalability**: Use relative units (rem, em) for sizing
3. **Focus states**: Ensure focused elements are visible
4. **Color independence**: Don't rely solely on color for meaning

### Code Blocks

1. **Font size**: Slightly smaller than body (14-15px)
2. **Line height**: Tighter than body text (1.4-1.5)
3. **Syntax highlighting**: Use contrasting colors
4. **Overflow**: Enable horizontal scrolling for long lines

## Custom Typography CSS

For advanced control, use custom CSS:

```css
/* Custom heading styles */
.prose h1 {
  font-weight: 800;
  letter-spacing: -0.025em;
}

.prose h2 {
  font-weight: 700;
  margin-top: 2em;
}

/* Custom paragraph styles */
.prose p {
  text-align: justify;
  hyphens: auto;
}

/* Custom code styles */
.prose code {
  font-size: 0.875em;
  padding: 0.2em 0.4em;
  background: #f3f4f6;
  border-radius: 0.25rem;
}

/* Custom blockquote */
.prose blockquote {
  font-style: normal;
  border-left: 4px solid var(--color-primary);
}
```

## Font Loading

Fonts are loaded from Google Fonts CDN with optimizations:

- **Display swap**: Text is visible immediately with fallback font
- **Preconnect**: DNS lookup is done early
- **Subset**: Only Latin characters loaded by default

For self-hosted fonts, place files in `public/fonts/` and reference in custom CSS.
