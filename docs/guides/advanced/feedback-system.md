---
title: Feedback System
description: Collect and manage user feedback on documentation
seo_title: Feedback System - Docs Starter Kit
status: published
---

# Feedback System

Collect user feedback to improve your documentation.

## Overview

The feedback system allows visitors to:
- Rate pages as helpful or not helpful
- Submit detailed feedback through custom forms
- Provide suggestions and report issues

Administrators can:
- View feedback analytics
- Manage feedback forms
- Export feedback data
- Identify problematic pages

## Enabling Feedback

### Global Setting

1. Go to **Settings > Advanced**
2. Enable **Feedback Widget**
3. Save changes

The "Was this page helpful?" widget appears on all documentation pages.

## Feedback Widget

### Default Behavior

The widget displays:
- "Was this page helpful?" prompt
- Yes/No buttons
- Optional follow-up form

### User Experience

1. User clicks Yes or No
2. If configured, follow-up form appears
3. User submits additional feedback
4. Thank you message displays

## Creating Feedback Forms

### Form Builder

1. Go to **Feedback > Forms**
2. Click **Create Form**
3. Configure form settings:
   - **Name**: Internal identifier
   - **Trigger**: When to show (positive, negative, always)
   - **Fields**: Form fields to collect

### Field Types

| Type | Description | Use Case |
|------|-------------|----------|
| Text | Single line input | Name, email |
| Textarea | Multi-line input | Detailed feedback |
| Radio | Single selection | Categories |
| Checkbox | Multiple selection | Issues list |
| Rating | Star rating | Satisfaction score |
| Select | Dropdown | Predefined options |

### Example: Negative Feedback Form

```json
{
  "name": "Negative Feedback",
  "trigger_type": "negative",
  "fields": [
    {
      "type": "checkbox",
      "label": "What was the issue?",
      "options": [
        "Information was incorrect",
        "Information was incomplete",
        "Hard to understand",
        "Code examples didn't work",
        "Page was outdated"
      ]
    },
    {
      "type": "textarea",
      "label": "Additional details",
      "placeholder": "Please describe the issue...",
      "required": false
    },
    {
      "type": "text",
      "label": "Email (optional)",
      "placeholder": "your@email.com",
      "required": false
    }
  ]
}
```

### Example: Positive Feedback Form

```json
{
  "name": "Positive Feedback",
  "trigger_type": "positive",
  "fields": [
    {
      "type": "textarea",
      "label": "What did you find helpful?",
      "placeholder": "Share what worked well...",
      "required": false
    }
  ]
}
```

## Viewing Feedback

### Dashboard

Go to **Feedback** in admin to see:
- Total responses
- Helpful vs not helpful ratio
- Recent feedback
- Pages with most feedback

### Filtering

Filter feedback by:
- Date range
- Response type (positive/negative)
- Page
- Has comments

### Page Analytics

View per-page metrics:
- Helpfulness score (%)
- Total responses
- Trend over time

## Exporting Data

### CSV Export

1. Go to **Feedback**
2. Apply filters if needed
3. Click **Export**
4. Download CSV file

### Data Included

- Submission date/time
- Page URL
- Response type
- Form responses
- User agent
- IP (anonymized)

## Privacy

- IP addresses are anonymized after 30 days
- Email addresses are optional
- GDPR compliant data handling
