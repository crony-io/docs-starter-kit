---
title: Security
description: Security features including 2FA, Turnstile, and best practices
seo_title: Security Features - Docs Starter Kit
status: published
---

# Security

Comprehensive security features to protect your documentation admin panel.

## Two-Factor Authentication (2FA)

Docs Starter Kit includes TOTP-based two-factor authentication via Laravel Fortify.

### Enabling 2FA

1. Go to **Settings > Two-Factor Authentication**
2. Click **Enable 2FA**
3. Scan the QR code with an authenticator app:
   - Google Authenticator
   - Authy
   - 1Password
   - Microsoft Authenticator
4. Enter the 6-digit code to confirm
5. Save your recovery codes securely

### Recovery Codes

When enabling 2FA, you receive 8 recovery codes. Each code can only be used once:

- Store them securely offline
- Use them if you lose access to your authenticator
- Regenerate codes after using any

### Disabling 2FA

1. Go to **Settings > Two-Factor Authentication**
2. Click **Disable 2FA**
3. Confirm the action

### Login with 2FA

After entering your password:

1. You'll be prompted for a 2FA code
2. Enter the 6-digit code from your authenticator
3. Or use a recovery code if needed

## Cloudflare Turnstile

Docs Starter Kit supports Cloudflare Turnstile for CAPTCHA protection on login.

### Configuration

1. Create a Turnstile widget at [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Go to **Settings > Advanced** in your admin panel
3. Enter your Turnstile credentials:
   - **Site Key**: Your Turnstile site key
   - **Secret Key**: Your Turnstile secret key
4. Save changes

Turnstile will automatically appear on the login page when configured.

> **Note**: Credentials are stored encrypted in the database.

### Benefits

- Invisible challenge for most users
- Protects against bots and brute force
- Privacy-friendly alternative to reCAPTCHA
- No user friction for legitimate users

## Rate Limiting

Built-in rate limiting protects against abuse:

### Protected Routes

| Route | Limit |
|-------|-------|
| Password update | 6 requests/minute |
| Feedback submission | 10 requests/minute |
| Search API | 30 requests/minute |
| Webhook | 10 requests/minute |
| Admin routes | 60 requests/minute |

### Configuration

Rate limits are defined in route middleware:

```php
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:10,1');
```

## Content Security Policy (CSP)

The application uses `spatie/laravel-csp` for Content Security Policy headers.

### Configuration

Edit `config/csp.php` to customize the policy:

```php
return [
    'enabled' => env('CSP_ENABLED', true),
    
    'policies' => [
        \App\Policies\ContentSecurityPolicy::class,
    ],
];
```

## Input Sanitization

### HTML Purifier

User content is sanitized using `mews/purifier`:

```php
$cleanHtml = clean($userInput);
```

Configuration in `config/purifier.php` defines allowed HTML elements and attributes.

### Markdown Rendering

Markdown content is rendered with security options:

```php
Str::markdown($content, [
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);
```

## Session Security

### Configuration

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true  # Enable in production
```

### Single Session Per User

When a user logs in, all their other active sessions are automatically terminated. This provides:

- **Security**: Prevents session hijacking
- **Control**: Only one active session per account
- **Awareness**: Users know if someone else logged in

### Session Termination Detection

The `DetectSessionTermination` middleware handles session expiry gracefully, redirecting to login without errors.

## Password Security

### Hashing

Passwords use bcrypt with configurable rounds:

```env
BCRYPT_ROUNDS=12
```

### Password Update

Users can update their password at **Settings > Password**:

- Current password required
- Minimum 8 characters
- Confirmation required

## Encryption

### Database Encryption

Sensitive fields are encrypted at rest:

```php
protected function casts(): array
{
    return [
        'git_access_token' => 'encrypted',
        'git_webhook_secret' => 'encrypted',
        'two_factor_secret' => 'encrypted',
    ];
}
```

### Application Key

The `APP_KEY` environment variable is used for all encryption:

```bash
php artisan key:generate
```

> **Never** share or commit your application key!

## Webhook Security

GitHub webhooks are verified using HMAC SHA-256:

1. GitHub signs payload with your secret
2. Server recalculates signature
3. Only matching signatures accepted

### Setup

1. Generate a strong secret:
   ```bash
   openssl rand -hex 32
   ```
2. Add to both GitHub webhook settings and admin panel

## File Upload Security

### Validation

```php
'file' => ['required', 'file', 'max:10240'], // 10MB max
'logo' => ['nullable', 'image', 'max:2048'],
'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:512'],
```

### Secure File Names

Uploaded files are renamed to prevent path traversal:

```php
$slug = str($name)->slug()->limit(50, '');
$timestamp = now()->format('YmdHis');
return "{$slug}-{$timestamp}.{$extension}";
```

## Security Headers

Configure security headers in your web server:

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

## HTTPS

### Forcing HTTPS

For production, force HTTPS in `AppServiceProvider`:

```php
if (config('app.env') === 'production') {
    URL::forceScheme('https');
}
```

### Secure Cookies

```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

## Security Checklist

Before going to production:

- [ ] Change default admin credentials
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Enable HTTPS
- [ ] Configure secure cookies
- [ ] Enable 2FA for all admin users
- [ ] Set up Cloudflare Turnstile (optional)
- [ ] Configure CSP headers
- [ ] Review rate limiting settings
- [ ] Set up regular backups
- [ ] Enable activity logging
- [ ] Review user access regularly

## Reporting Vulnerabilities

If you discover a security vulnerability, please report it responsibly by emailing the project maintainers rather than opening a public issue.
