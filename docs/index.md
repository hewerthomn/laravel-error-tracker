# Laravel Error Tracker

A Laravel-first error tracking package with a built-in dashboard, local
persistence, issue grouping, notifications, smart stack traces, diagnostics, and
optional end-user feedback.

## Features

### Capture and storage

- Exception capture through Laravel's exception pipeline.
- Local database storage inside the project or in a shared external database.
- Issue grouping by fingerprint.
- Hourly trend aggregation.
- Optional shared tracker database connection for multiple applications or environments.

### Dashboard and search

- Dashboard with grouped issues, filters, issue detail, and event detail.
- Advanced search with shareable GET query parameters.
- Configurable dashboard navigation back to the host application.
- Smart stack trace grouping with project frame highlighting.

### Issue workflow

- Resolve, reopen, ignore, mute, and unmute issues.
- Optional auto resolve for stale open issues.

### Notifications

- Mail and Slack notifications for new and reactivated issues.
- Optional notification cooldown to reduce noisy alerts.

### Feedback and error page

- Optional custom production error page.
- Optional end-user feedback form linked to recorded events.

### Diagnostics and maintenance

- Configuration diagnostics for capture, feedback, notifications, stack trace,
  retention, redaction, and database health checks.
- Prune, auto resolve, demo data, and doctor Artisan commands.

## Screenshots

### Issues dashboard

![Issues dashboard](./screenshots/dashboard-index.png)

### Issue detail

![Issue detail](./screenshots/issue-detail.png)

### Smart stack trace

![Smart stack trace](./screenshots/event-detail-smart-stacktrace.png)

### Configuration diagnostics

![Configuration diagnostics](./screenshots/configuration-diagnostics.png)

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13
- A relational database supported by Laravel

## Next steps

- [Install the package](./installation.md)
- [Configure basic exception capture](./basic-setup.md)
- [Review the dashboard](./dashboard.md)
- [Run diagnostics](./diagnostics.md)
