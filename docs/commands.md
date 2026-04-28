# Commands

Laravel Error Tracker ships with Artisan commands for installation, diagnostics,
demo data, pruning, and stale issue resolution.

## Install

```bash
php artisan error-tracker:install
```

Publishes configuration and migrations and prints the next setup steps.

```bash
php artisan error-tracker:install --guided
```

Runs the guided installer.

```bash
php artisan error-tracker:install --preset=local
php artisan error-tracker:install --preset=production
php artisan error-tracker:install --preset=minimal
php artisan error-tracker:install --preset=demo
```

Uses an installation preset.

## Doctor

```bash
php artisan error-tracker:doctor
```

Runs installation and configuration diagnostics.

```bash
php artisan error-tracker:doctor --json
```

Prints diagnostics as JSON.

```bash
php artisan error-tracker:doctor --fail-on-missing
```

Returns a non-zero exit code if required resources are missing.

## Demo data

```bash
php artisan error-tracker:demo --fresh --with-feedback --with-notifications --with-resolved
```

Generates safe demo records for local testing and screenshots.

```bash
php artisan error-tracker:demo --purge
```

Removes only demo data.

Demo issue fingerprints start with `demo:` and event context is marked with
`_demo=true`.

## Prune

```bash
php artisan error-tracker:prune
```

Deletes old events, feedback, and resolved issues according to the retention
configuration.

```bash
php artisan error-tracker:prune --dry-run
```

Shows what would be pruned without deleting anything.

## Auto Resolve

```bash
php artisan error-tracker:auto-resolve
```

Automatically resolves stale open issues when Auto Resolve is enabled.

```bash
php artisan error-tracker:auto-resolve --dry-run
```

Shows eligible stale issues without changing them.

```bash
php artisan error-tracker:auto-resolve --days=30
```

Temporarily overrides the configured stale threshold.

## Scheduler examples

Add these to `routes/console.php` in the host application when needed:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('error-tracker:prune')->daily();
Schedule::command('error-tracker:auto-resolve')->daily();
```

Remember that Laravel scheduled commands require the application scheduler to be
running in production.
