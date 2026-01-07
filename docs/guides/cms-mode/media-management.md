---
title: Media Management
description: Upload and manage images and files
seo_title: Media Management - Docs Starter Kit
status: published
---

# Media Management

Upload, organize, and embed media files in your documentation.

## Accessing File Manager

Navigate to **Media** in the admin sidebar to access the file manager.

## Uploading Files

### Single File Upload

1. Click **Upload** button
2. Select file from your computer
3. Wait for upload to complete
4. File appears in library

### Drag and Drop

1. Drag files from your computer
2. Drop onto the file manager area
3. Multiple files upload simultaneously

### Supported File Types

**Images**:
- PNG, JPG, JPEG, GIF
- WebP, SVG
- ICO, BMP

**Documents**:
- PDF
- DOCX, DOC
- XLSX, XLS
- PPTX, PPT

**Other**:
- ZIP, RAR
- MP4, WebM (video)
- MP3, WAV (audio)

### Size Limits

- **Default**: 10MB per file
- **Images**: Automatically optimized
- **Configurable**: Adjust in settings

## Organizing Files

### Folders

Create folders to organize your media:

1. Click **New Folder**
2. Enter folder name
3. Drag files into folders
4. Navigate with breadcrumbs

### Folder Structure

Recommended organization:

```
media/
├── images/
│   ├── screenshots/
│   ├── diagrams/
│   └── icons/
├── downloads/
│   ├── templates/
│   └── examples/
└── videos/
```

## File Operations

### View File Details

Click a file to see:
- File name and type
- Dimensions (for images)
- File size
- Upload date
- URL for embedding

### Rename Files

1. Select file
2. Click **Rename** or press `F2`
3. Enter new name
4. Press Enter

### Move Files

1. Select one or more files
2. Drag to destination folder
3. Or use **Move** button

### Delete Files

1. Select files
2. Click **Delete** or press `Delete`
3. Confirm deletion

> **Warning**: Deleting files may break images embedded in documentation.

### Bulk Operations

1. Hold `Ctrl/Cmd` to select multiple files
2. Or use checkbox selection mode
3. Apply operation to all selected

## Embedding Media

### In Editor

1. Place cursor where you want the image
2. Click image icon in toolbar
3. Click **Browse** to open file manager
4. Select image
5. Add alt text
6. Insert

### Direct URL

Copy the file URL from file details:

```
/storage/media/images/screenshot.png
```

Use in markdown:

```markdown
![Screenshot description](/storage/media/images/screenshot.png)
```

### Responsive Images

The editor automatically creates responsive image markup:

```html
<img 
  src="/storage/media/images/screenshot.png"
  alt="Screenshot description"
  loading="lazy"
/>
```

## Image Optimization

### Automatic Processing

Uploaded images are automatically:
- Converted to efficient formats
- Resized to reasonable dimensions
- Compressed for web delivery

### Manual Optimization

Before uploading, optimize images:

1. Resize to maximum needed dimensions
2. Use appropriate format:
   - **PNG**: Screenshots, diagrams, transparency
   - **JPG**: Photos, complex images
   - **SVG**: Icons, logos, simple graphics
   - **WebP**: Modern browsers, best compression
3. Compress using tools like TinyPNG

### Recommended Sizes

| Use Case | Max Width | Format |
|----------|-----------|--------|
| Screenshots | 1200px | PNG |
| Photos | 1600px | JPG |
| Thumbnails | 400px | JPG/WebP |
| Icons | 64px | SVG/PNG |
| Diagrams | 1000px | SVG/PNG |

## Search and Filter

### Search

Use the search bar to find files by:
- File name
- File type
- Folder name

### Filter

Filter by:
- File type (images, documents, etc.)
- Upload date
- Folder

### Sort

Sort files by:
- Name (A-Z, Z-A)
- Date (newest, oldest)
- Size (largest, smallest)
- Type

## Storage

### Local Storage

Default: Files stored in `storage/app/public/media/`

Ensure symbolic link exists:
```bash
php artisan storage:link
```

### Cloud Storage (S3)

Configure in `.env`:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

## Best Practices

### Naming

- Use descriptive names: `dashboard-overview.png`
- Use lowercase and hyphens
- Include version if applicable: `logo-v2.svg`

### Organization

- Create logical folder structure
- Group by type or feature
- Archive old/unused files

### Performance

- Optimize images before upload
- Use appropriate formats
- Lazy load large images
- Consider CDN for high-traffic sites

### Accessibility

- Always add alt text to images
- Describe image content, not just filename
- For decorative images, use empty alt: `alt=""`

## Troubleshooting

### Upload Fails

1. Check file size limits
2. Verify file type is allowed
3. Check storage permissions
4. Review PHP upload limits

### Images Not Displaying

1. Verify storage link: `php artisan storage:link`
2. Check file permissions
3. Confirm file exists at path
4. Clear browser cache
