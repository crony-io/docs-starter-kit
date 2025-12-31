---
title: Webhooks
description: Set up GitHub webhooks for instant documentation updates
seo_title: Webhook Configuration - Docs Starter Kit
order: 3
status: published
---

# Webhooks

Configure GitHub webhooks for instant documentation updates when you push changes.

## Why Use Webhooks?

Without webhooks, documentation syncs on a schedule (default: every 15 minutes). With webhooks:

- **Instant updates**: Changes appear within seconds of pushing
- **Reduced server load**: No constant polling
- **Better control**: Only sync when changes occur
- **Event logging**: Track all sync triggers

## Setup Guide

### Step 1: Get Your Webhook URL

1. Go to **Git Sync** in your admin panel
2. Find your webhook URL displayed in the configuration section:

```
https://yourdomain.com/webhook/github
```

### Step 2: Generate Webhook Secret

1. In **Git Sync** settings, set a **Webhook Secret**
2. Use a strong, random string (recommended: 32+ characters)
3. Save your configuration

You can generate a secret with:

```bash
openssl rand -hex 32
```

### Step 3: Configure GitHub Webhook

1. Go to your GitHub repository
2. Navigate to **Settings > Webhooks > Add webhook**
3. Configure the webhook:

| Setting | Value |
|---------|-------|
| Payload URL | `https://yourdomain.com/webhook/github` |
| Content type | `application/json` |
| Secret | Your webhook secret from Step 2 |
| SSL verification | Enable (recommended) |
| Events | Just the push event |

4. Click **Add webhook**

### Step 4: Test the Webhook

1. Make a small change to your documentation repository
2. Commit and push the change
3. Check webhook delivery in GitHub:
   - Go to **Settings > Webhooks > Recent Deliveries**
   - Verify you see a green checkmark (200 response)
4. Check your admin panel:
   - Go to **Git Sync**
   - Verify a new sync appears in history

## Webhook Security

### Signature Verification

Docs Starter Kit verifies webhook signatures using HMAC SHA-256:

1. GitHub signs each payload with your secret
2. The server recalculates the signature
3. Only matching signatures are accepted

This prevents unauthorized sync triggers.

### Best Practices

1. **Use HTTPS**: Always use SSL for webhook URLs
2. **Strong secrets**: Use 32+ character random strings
3. **Rotate secrets**: Change secrets periodically
4. **Monitor deliveries**: Check GitHub for failed deliveries
5. **Rate limiting**: Webhook endpoint is protected against abuse

## Webhook Events

### Supported Events

Currently, only `push` events trigger synchronization:

```json
{
  "ref": "refs/heads/main",
  "commits": [
    {
      "id": "abc123...",
      "message": "Update documentation",
      "author": {
        "name": "John Doe"
      }
    }
  ]
}
```

### Branch Filtering

Only pushes to your configured branch trigger sync:

- Configured branch: `main`
- Push to `main` → Sync triggered
- Push to `develop` → Ignored

## Troubleshooting

### Webhook Returns 401 Unauthorized

**Cause**: Invalid webhook signature

**Solutions**:
1. Verify secret matches in both GitHub and admin panel
2. Ensure no trailing whitespace in secret
3. Re-save the secret in both places

### Webhook Returns 200 but No Sync

**Cause**: Push to wrong branch or Git mode disabled

**Solutions**:
1. Verify push was to configured branch
2. Check content mode is set to `git`
3. Check queue worker is running

### Webhook Not Receiving Events

**Cause**: GitHub can't reach your server

**Solutions**:
1. Verify URL is publicly accessible
2. Check SSL certificate is valid
3. Verify firewall allows GitHub IPs
4. Check GitHub webhook deliveries for errors

### Delayed Sync After Webhook

**Cause**: Queue worker not processing jobs

**Solutions**:
1. Ensure queue worker is running:
   ```bash
   php artisan queue:work
   ```
2. Check for failed jobs:
   ```bash
   php artisan queue:failed
   ```
3. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

## GitHub IPs

If you use a firewall, allow GitHub webhook IPs. Get current IPs:

```bash
curl https://api.github.com/meta | jq '.hooks'
```

Or visit: https://api.github.com/meta

## Alternative: GitHub Actions

You can also trigger syncs via GitHub Actions:

```yaml
# .github/workflows/docs-sync.yml
name: Documentation Sync

on:
  push:
    branches:
      - main
    paths:
      - 'docs/**'
      - 'assets/**'

jobs:
  trigger-sync:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger webhook
        run: |
          curl -X POST "${{ secrets.DOCS_WEBHOOK_URL }}" \
            -H "Content-Type: application/json" \
            -H "X-GitHub-Event: push" \
            -H "X-Hub-Signature-256: sha256=$(echo -n '{}' | openssl dgst -sha256 -hmac '${{ secrets.WEBHOOK_SECRET }}' | awk '{print $2}')" \
            -d '{}'
```

Add secrets to your repository:
- `DOCS_WEBHOOK_URL`: Your webhook URL
- `WEBHOOK_SECRET`: Your webhook secret
