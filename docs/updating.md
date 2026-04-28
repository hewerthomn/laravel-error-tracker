# Updating the Package

New Error Tracker features can add database migrations. After updating the
package, publish any new migrations, run them, clear optimized config, and run
diagnostics:

```bash
composer update hewerthomn/laravel-error-tracker -W
php artisan vendor:publish --tag=error-tracker-migrations
php artisan migrate
php artisan optimize:clear
php artisan error-tracker:doctor
```

Examples of features that add schema are Auto Resolve metadata and Notification
Cooldown history.
