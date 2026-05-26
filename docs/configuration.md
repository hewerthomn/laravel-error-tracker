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
- `integrations.issue_trackers.github`

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

## GitHub Issue Integration

GitHub issue linking is disabled by default.

| Environment variable | Config key | Default |
| --- | --- | --- |
| `ERROR_TRACKER_GITHUB_ENABLED` | `integrations.issue_trackers.github.enabled` | `false` |
| `ERROR_TRACKER_GITHUB_TOKEN` | `integrations.issue_trackers.github.token` | empty |
| `ERROR_TRACKER_GITHUB_REPOSITORY` | `integrations.issue_trackers.github.repository` | empty |
| `ERROR_TRACKER_GITHUB_LABELS` | `integrations.issue_trackers.github.labels` | `bug,error-tracker` |
| `ERROR_TRACKER_GITHUB_ASSIGNEES` | `integrations.issue_trackers.github.assignees` | empty |
| `ERROR_TRACKER_GITHUB_TITLE_TEMPLATE` | `integrations.issue_trackers.github.title_template` | `[{environment}] {exception_class} in {location}` |
| `ERROR_TRACKER_GITHUB_INCLUDE_STACK_TRACE` | `integrations.issue_trackers.github.include_stack_trace` | `true` |
| `ERROR_TRACKER_GITHUB_MAX_STACK_FRAMES` | `integrations.issue_trackers.github.max_stack_frames` | `15` |
| `ERROR_TRACKER_GITHUB_INCLUDE_CONTEXT` | `integrations.issue_trackers.github.include_context` | `false` |
| `ERROR_TRACKER_GITHUB_INCLUDE_SOURCE_CONTEXT` | `integrations.issue_trackers.github.include_source_context` | `false` |

`ERROR_TRACKER_GITHUB_TOKEN` is displayed only as `configured` or
`not configured` in diagnostics.
