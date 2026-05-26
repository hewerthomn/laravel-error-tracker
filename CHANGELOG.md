# Changelog

All notable changes to laravel-error-tracker will be documented in this file.

## [1.1.0] - Unreleased

### Added

- GitHub Issue Integration.
- Create GitHub issues from internal Error Tracker issues.
- Link existing GitHub issues by URL, `owner/repository#number`, `#number`, or bare number.
- Manual refresh from GitHub for linked issue title, state, URL, and metadata.
- Unlink support that removes only the local external issue association.
- Generic external issue persistence for future providers.

## [1.0.0] - 2026-04-28

### Added

- Initial stable release.
- Exception capture through Laravel's exception pipeline.
- Dashboard with issue grouping, filters, issue detail, and event detail.
- Smart stack trace grouping with source context.
- Advanced search.
- Auto Resolve.
- Notification cooldown.
- User feedback support.
- Configuration diagnostics page.
- `error-tracker:doctor`.
- `error-tracker:demo`.
- Guided installer.
- VitePress documentation.

## Unreleased

### Added

* Initial package structure.
* Exception capture and grouping.
* Dashboard for issues and events.
* Issue status actions.
* Notifications.
* Custom production error page.
* User feedback form.
* Prune command.
* Pest test suite.
