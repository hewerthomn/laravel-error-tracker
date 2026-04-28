# Configuration

Main configuration file:

```text
config/error-tracker.php
```

Important options include:

- `database.connection`
- `route.path`
- `dashboard.app_home_url`
- `capture.sample_rate`
- `fingerprint.include_environment`
- `notifications.channels`
- `notifications.cooldown_minutes`
- `notifications.max_per_issue_per_hour`
- `error_page.enabled`
- `feedback.enabled`
- `retention.events_days`
- `auto_resolve.enabled`
- `auto_resolve.after_days`
- `stacktrace.smart_grouping`
- `stacktrace.project_paths`
- `stacktrace.project_namespaces`
- `stacktrace.non_project_paths`
- `stacktrace.path_display`
- `stacktrace.store_absolute_paths`
- `stacktrace.source_context`
- `stacktrace.store_arguments`

## Shared Database / Multi-App Setup

The package can optionally use a dedicated database connection:

```php
'database' => [
    'connection' => env('ERROR_TRACKER_DB_CONNECTION'),
],
```

When this is configured, multiple applications may write to the same tracker
storage.

In that setup, keeping `environment` visible in the dashboard and optionally
including it in the fingerprint becomes more useful.
