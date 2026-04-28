# Screenshots

Use demo data only when capturing screenshots for the main README.

## Recommended flow

1. Run migrations:

```bash
php artisan migrate
```

2. Generate demo data:

```bash
php artisan error-tracker:demo --fresh --with-feedback --with-notifications --with-resolved
```

3. Capture these screens:

* `dashboard-index.png`: dashboard index at `/error-tracker`
* `issue-detail.png`: an open critical production issue
* `event-detail-smart-stacktrace.png`: event detail with the Smart Stack Trace tab visible
* `configuration-diagnostics.png`: configuration diagnostics at `/error-tracker/configuration`
* `feedback-page.png`: feedback page, when applicable

4. Suggested resolution:

* 1440x1000
* 1600x1000

5. Safety checks:

* Use only demo data.
* Do not show a real domain.
* Do not show real email addresses.
* Do not show tokens or secrets.
* Do not show sensitive internal paths.

The demo command creates only records whose issue fingerprint starts with `demo:` and marks event context with `_demo: true`.
