# Auto Resolve

Auto Resolve can close stale open issues when they have not received new events
for a configured number of days.

The feature is disabled by default.

Default configuration:

```php
'auto_resolve' => [
    'enabled' => env('ERROR_TRACKER_AUTO_RESOLVE_ENABLED', false),
    'after_days' => env('ERROR_TRACKER_AUTO_RESOLVE_AFTER_DAYS', 14),
    'statuses' => ['open'],
    'levels' => ['warning', 'error'],
    'environments' => null,
    'reason' => 'Automatically resolved after :days days without new events.',
],
```

Example scheduler registration:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('error-tracker:auto-resolve')->daily();
```

Preview eligible issues without changing the database:

```bash
php artisan error-tracker:auto-resolve --dry-run
```

Run Auto Resolve:

```bash
php artisan error-tracker:auto-resolve
```
