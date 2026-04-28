# Installation

## Default install

Install the package in your Laravel application, publish configuration and
migrations, run migrations, and verify the setup:

```bash
composer require hewerthomn/laravel-error-tracker
php artisan error-tracker:install
php artisan migrate
php artisan error-tracker:doctor
```

The default installer is non-destructive. It publishes the package config and
migrations, optionally asks to run migrations in interactive terminals, and
prints the next recommended commands.

To generate demo data at the end of installation:

```bash
php artisan error-tracker:install --with-demo
```

## Guided install

```bash
php artisan error-tracker:install --guided
```

The guided installer can suggest the main `.env` values for feedback, custom
error pages, auto resolve, notifications, notification cooldown, smart stack
trace, and database connection.

It does not edit `config/error-tracker.php` directly. To write missing `.env`
values idempotently, pass `--write-env`.

## Presets

```bash
php artisan error-tracker:install --preset=local
php artisan error-tracker:install --preset=production
php artisan error-tracker:install --preset=minimal
php artisan error-tracker:install --preset=demo
```

Available presets:

- `minimal`: feedback off, notifications off, auto resolve off, custom error
  page off, smart stack trace on.
- `local`: feedback on, notifications off, auto resolve off, custom error page
  on, smart stack trace on.
- `production`: feedback on, notifications on, auto resolve off, custom error
  page on, smart stack trace on, notification cooldown on.
- `demo`: feedback on, notifications off, auto resolve on, custom error page
  on, smart stack trace on, and demo data generation enabled.
