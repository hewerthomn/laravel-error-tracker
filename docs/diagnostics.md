# Diagnostics

## Configuration page

The read-only diagnostics page is available at:

```text
/error-tracker/configuration
```

If you customize `error-tracker.route.path`, the page follows that path, for
example `/{custom-path}/configuration`.

The page shows the effective Error Tracker configuration for capture, feedback,
auto resolve, notifications, GitHub Issue Integration, stack trace, retention,
redaction, and database health checks. It also shows command and scheduler
hints for maintenance tasks.

Secrets are never displayed raw. Notification recipients, Slack webhook values,
tokens, secrets, passwords, authorization headers, cookies, and API keys are
rendered as `configured` or `not configured`.

GitHub diagnostics include:

- `error_tracker_external_issues` table exists;
- GitHub Issue Integration enabled or disabled;
- GitHub repository configured or not configured;
- GitHub token configured or not configured.

The GitHub token is always masked.

## Doctor command

Run upgrade and configuration diagnostics:

```bash
php artisan error-tracker:doctor
```

Run JSON diagnostics for CI:

```bash
php artisan error-tracker:doctor --json --fail-on-missing
```

The doctor command does not call the GitHub API by default. It checks local
configuration and database state only.

## Production checklist

- Configure the `viewErrorTracker` gate.
- Keep `APP_DEBUG=false`.
- Review redaction rules.
- Review feedback settings.
- Review GitHub Issue Integration settings if enabled.
- Configure mail or Slack notifications if needed.
- Run `php artisan error-tracker:doctor`.
- Schedule `error-tracker:prune`.
- Schedule `error-tracker:auto-resolve` if Auto Resolve is enabled.
