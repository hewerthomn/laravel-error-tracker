# Demo Data

Generate safe demo records for local testing and screenshots:

```bash
php artisan error-tracker:demo --fresh --with-feedback --with-notifications --with-resolved
```

Purge only demo data:

```bash
php artisan error-tracker:demo --purge
```

Demo data safety:

- Demo issue fingerprints start with `demo:`.
- Event context is marked with `_demo=true`.
- The command does not remove real data.

## Screenshot capture flow

Use demo data only when capturing screenshots for the main README or
documentation site.

Recommended screens:

- `dashboard-index.png`: dashboard index at `/error-tracker`
- `issue-detail.png`: an open critical production issue
- `event-detail-smart-stacktrace.png`: event detail with the Smart Stack Trace tab visible
- `configuration-diagnostics.png`: configuration diagnostics at `/error-tracker/configuration`

Safety checks:

- Use only demo data.
- Do not show a real domain.
- Do not show real email addresses.
- Do not show tokens or secrets.
- Do not show sensitive internal paths.
