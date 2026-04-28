# Notifications

Supported in the current MVP:

- Mail
- Slack

Mail notifications can be configured with:

```text
ERROR_TRACKER_MAIL_TO=alerts@example.test
```

Slack delivery is optional and depends on Laravel's Slack notification channel
setup in the host application.

## Notification Cooldown

Notification cooldown prevents a noisy issue from sending too many mail or Slack
alerts in a short period.

The limiter is applied per issue and covers notifications for:

- `new_issue`
- `regression`
- `reactivated`

Default settings:

```text
ERROR_TRACKER_NOTIFICATION_COOLDOWN_MINUTES=30
ERROR_TRACKER_NOTIFICATION_MAX_PER_ISSUE_PER_HOUR=3
```

`ERROR_TRACKER_NOTIFICATION_COOLDOWN_MINUTES` defines the minimum time between
notifications for the same issue.

`ERROR_TRACKER_NOTIFICATION_MAX_PER_ISSUE_PER_HOUR` caps how many notifications
a single issue can send in a rolling one-hour window.

Set either value to `0` or leave it `null` to disable that specific limit:

```text
ERROR_TRACKER_NOTIFICATION_COOLDOWN_MINUTES=0
ERROR_TRACKER_NOTIFICATION_MAX_PER_ISSUE_PER_HOUR=0
```

The issue detail page shows recent notification metadata, and the configuration
page shows the effective cooldown and hourly limit values.
