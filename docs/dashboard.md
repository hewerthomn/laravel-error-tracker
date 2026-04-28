# Dashboard

By default, the dashboard is available at:

```text
/error-tracker
```

The page title uses:

```text
Error Tracker - {APP_NAME}
```

The dashboard also supports a configurable shortcut back to the host
application.

## Issues

The dashboard shows grouped issues with issue detail and event detail pages.

Issue status actions include:

- Resolve
- Reopen
- Ignore
- Mute
- Unmute

## Quick filters

The issues dashboard includes a left sidebar with quick filters for status,
level, period, and environment.

The main issue list includes search for errors, paths, or messages, plus sorting
by recent, frequent, or oldest issues. Filter links preserve the current query
string, and status and level filters show counts for the current dashboard
slice.

## Trends

Error Tracker stores hourly trend aggregation so the dashboard can show issue
activity over time.
