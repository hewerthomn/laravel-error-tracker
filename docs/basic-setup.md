# Basic Setup

## Define the dashboard gate

In your host application, define the `viewErrorTracker` gate:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewErrorTracker', function ($user = null) {
    return true;
});
```

Replace the example above with your real authorization logic.

## Minimal exception capture

Register exception capture in `bootstrap/app.php`:

```php
use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Illuminate\Foundation\Configuration\Exceptions;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->dontReportDuplicates();

    $exceptions->report(function (\Throwable $e) {
        app(RecordThrowableAction::class)->handle($e);
    });
})
```

For the full `bootstrap/app.php` example that records the exception and renders
the optional production error page with feedback, see [Feedback](./feedback.md).
