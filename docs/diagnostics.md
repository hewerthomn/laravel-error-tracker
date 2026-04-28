# Diagnostics

## Configuration page

The read-only diagnostics page is available at:

```text
/error-tracker/configuration
```

If you customize `error-tracker.route.path`, the page follows that path, for
example `/{custom-path}/configuration`.

The page shows the effective Error Tracker configuration for capture, feedback,
auto resolve, notifications, stack trace, retention, redaction, and database
health checks. It also shows command and scheduler hints for maintenance tasks.

Secrets are never displayed raw. Notification recipients, Slack webhook values,
tokens, secrets, passwords, authorization headers, cookies, and API keys are
rendered as `configured` or `not configured`.

## Doctor command

Run upgrade and configuration diagnostics:

```bash
php artisan error-tracker:doctor
```

Run JSON diagnostics for CI:

```bash
php artisan error-tracker:doctor --json --fail-on-missing
```

## Production checklist

- Configure the `viewErrorTracker` gate.
- Keep `APP_DEBUG=false`.
- Review redaction rules.
- Review feedback settings.
- Configure mail or Slack notifications if needed.
- Run `php artisan error-tracker:doctor`.
- Schedule `error-tracker:prune`.
- Schedule `error-tracker:auto-resolve` if Auto Resolve is enabled.
