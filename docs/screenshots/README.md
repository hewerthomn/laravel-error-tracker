# Screenshots

Use demo data only when capturing screenshots for the main README.

```bash
php artisan migrate
php artisan error-tracker:demo --fresh --with-feedback --with-notifications --with-resolved
```

Suggested captures:

* `dashboard.png`: `/error-tracker`
* `issue-detail.png`: an open critical production issue
* `event-detail.png`: an event with the Smart Stack Trace tab visible
* `feedback.png`: an event that has user feedback
* `diagnostics.png`: `/error-tracker/configuration`

Suggested viewport: 1440x1000 for desktop screenshots. Avoid using real application errors, real users, real request URLs, or production secrets.
