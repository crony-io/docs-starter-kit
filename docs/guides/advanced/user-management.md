---
title: User Management
description: Manage admin users and access control
seo_title: User Management - Docs Starter Kit
status: published
---

# User Management

Manage admin users who can access the documentation admin panel.

## Overview

Docs Starter Kit provides a user management system for:

- Creating new admin users
- Editing user information
- Managing user access
- Deleting non-admin users

## Accessing User Management

Navigate to **Users** in the admin sidebar. This feature is only available to admin users.

## User Roles

Currently, the system has two user types:

| Role | Access |
|------|--------|
| Admin | Full access to all features including user management |
| User | Access to admin panel features except user management |

### Checking Admin Status

```php
$user->isAdmin(); // Returns true/false
```

## Creating Users

1. Go to **Users**
2. Click **Create User**
3. Fill in the details:
   - **Name**: User's display name
   - **Email**: Login email (must be unique)
   - **Password**: Secure password
   - **Confirm Password**: Re-enter password
4. Click **Create**

New users are created with verified email status and can log in immediately.

## Editing Users

1. Go to **Users**
2. Click **Edit** on the user row
3. Update information:
   - Name
   - Email
   - Password (optional, leave blank to keep current)
4. Click **Update**

## Deleting Users

1. Go to **Users**
2. Click **Delete** on the user row
3. Confirm deletion

**Restrictions:**
- Admin users cannot be deleted
- Users cannot delete themselves

## Default Admin Account

After installation with seeders, a default admin account is created:

| Field | Value |
|-------|-------|
| Name | Admin |
| Email | admin@example.com |
| Password | password |

> **Important**: Change these credentials immediately in production!

## Security Considerations

### Password Hashing

Passwords are automatically hashed using bcrypt:

```env
BCRYPT_ROUNDS=12
```

### Admin Protection

The `UserManagementController` has middleware that:

1. Checks if user is authenticated
2. Verifies user has admin role
3. Returns 403 if unauthorized

```php
if (! $request->user()->isAdmin()) {
    abort(403, 'Unauthorized access.');
}
```

### Self-Deletion Prevention

Users cannot delete their own account through the user management interface.

## Database Schema

The `users` table includes:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Display name |
| email | string | Unique login email |
| email_verified_at | timestamp | Verification date |
| password | string | Hashed password |
| is_admin | boolean | Admin role flag |
| two_factor_secret | text | 2FA secret (encrypted) |
| two_factor_recovery_codes | text | 2FA backup codes (encrypted) |
| two_factor_confirmed_at | timestamp | 2FA confirmation date |
| remember_token | string | Session remember token |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last update date |

## API Reference

### UserManagementController

| Method | Route | Action |
|--------|-------|--------|
| GET | `/users` | List all users |
| POST | `/users` | Create new user |
| GET | `/users/{user}/edit` | Edit user form |
| PATCH | `/users/{user}` | Update user |
| DELETE | `/users/{user}` | Delete user |

### Validation Rules

**Create User:**
```php
'name' => 'required|string|max:255',
'email' => 'required|email|unique:users,email',
'password' => 'required|string|min:8|confirmed',
```

**Update User:**
```php
'name' => 'required|string|max:255',
'email' => 'required|email|unique:users,email,'.$user->id,
'password' => 'nullable|string|min:8|confirmed',
```

## Best Practices

1. **Limit Admins**: Only grant admin access to those who need it
2. **Strong Passwords**: Enforce minimum 8 characters
3. **Regular Audits**: Review user list periodically
4. **Enable 2FA**: Encourage all users to enable two-factor authentication
5. **Change Default Credentials**: Never use default admin credentials in production
