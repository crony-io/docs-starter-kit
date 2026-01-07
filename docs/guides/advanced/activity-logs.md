---
title: Activity Logs
description: Monitor and audit user activity across your documentation site
seo_title: Activity Logs - Docs Starter Kit
status: published
---

# Activity Logs

Track and audit all user activity on your documentation admin panel.

## Overview

The Activity Logs feature provides comprehensive tracking of:

- User authentication events (login, logout)
- Content changes (create, update, delete pages)
- Settings modifications
- File uploads and management
- Data exports
- All admin panel actions

## Accessing Activity Logs

Navigate to **Activity Logs** in the admin sidebar to view all logged activity.

## Log Information

Each activity log entry contains:

| Field | Description |
|-------|-------------|
| User | Who performed the action |
| Action | Type of action (query, create, update, delete, login, logout) |
| Route | Laravel route name |
| URL | Full request URL |
| Method | HTTP method (GET, POST, PUT, PATCH, DELETE) |
| IP Address | Client IP (supports Cloudflare) |
| Status Code | HTTP response status |
| Execution Time | Request duration in milliseconds |
| Date | When the action occurred |

## Filtering Logs

Filter activity logs by:

- **User**: Select specific user
- **Action**: Filter by action type
- **Route**: Filter by route name
- **Date Range**: Start and end date
- **Status**: Successful or error responses

## Log Statistics

The dashboard displays:

- **Total Logs**: All recorded activities
- **Today**: Activities in the last 24 hours
- **Errors**: Failed requests (4xx, 5xx status codes)
- **Successful**: Completed requests (2xx status codes)
- **Redirects**: Redirect responses (3xx status codes)

## Viewing Log Details

Click any log entry to view detailed information:

- Full request data (sanitized)
- Response data
- Request headers
- IP geolocation information
- Session and metadata

## Exporting Logs

Export activity logs for external analysis:

1. Go to **Activity Logs**
2. Apply filters if needed
3. Click **Export**
4. Download CSV file

### Exported Fields

- ID, User, Action, Route
- URL, Method, IP, Status Code
- Execution Time, Controller
- Description, Date

## Cleaning Old Logs

Remove old logs to manage database size:

1. Go to **Activity Logs**
2. Click **Clean Old Logs**
3. Enter number of days to retain
4. Confirm deletion

```bash
# Via artisan (example for logs older than 90 days)
php artisan tinker
>>> App\Services\ActivityLogService::cleanOldLogs(90)
```

## Privacy & Security

### IP Anonymization

The system supports IP anonymization for GDPR compliance:

```bash
php artisan app:anonymize-old-ip-addresses
```

This command anonymizes IP addresses in logs older than configured days.

### Sensitive Data Filtering

The following are automatically excluded from logs:

- Passwords and tokens
- Two-factor secrets
- Session tokens
- Authorization headers
- Cookies

### Cloudflare Support

Activity logging properly detects real client IPs when using Cloudflare:

- Reads `CF-Connecting-IP` header
- Falls back to `X-Forwarded-For`
- Handles proxy chains correctly

## IP Detection

The `IpDetectionService` provides:

- Real IP detection behind proxies
- IP geolocation lookup
- Server IP filtering (internal requests excluded)
- Private IP range detection

## Configuration

### Excluded Routes

Some routes are automatically excluded from logging:

- Static assets (CSS, JS, images, fonts)
- Health check endpoints (`/up`, `/health`)
- Debug routes (Debugbar, Telescope)
- Activity log routes (prevent recursion)

### Execution Time Tracking

All requests are timed and stored in milliseconds, useful for:

- Performance monitoring
- Identifying slow requests
- Debugging bottlenecks

## Best Practices

1. **Regular Cleanup**: Schedule periodic log cleanup to manage database size
2. **Monitor Errors**: Review error logs regularly for issues
3. **Export Before Cleanup**: Export important logs before deletion
4. **Review Login Attempts**: Monitor for suspicious authentication activity
5. **Check IP Geolocation**: Identify unusual access patterns

## Technical Details

### Models

```php
use App\Models\ActivityLog;

// Query scopes
ActivityLog::forUser($userId)->get();
ActivityLog::forAction('login')->get();
ActivityLog::forRoute('admin.pages.store')->get();
ActivityLog::inDateRange($start, $end)->get();
ActivityLog::successful()->get();
ActivityLog::withErrors()->get();
```

### Service

```php
use App\Services\ActivityLogService;

$service = app(ActivityLogService::class);

// Get filtered logs
$logs = $service->getLogs([
    'user_id' => 1,
    'action' => 'create',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
]);

// Clean old logs
$deleted = $service->cleanOldLogs(90);
```
