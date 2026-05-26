# Changelog

All notable changes to laravel-error-tracker will be documented in this file.

## [Unreleased]

## [1.1.0] - 2026-05-26

### Added

- GitHub Issue Integration.
- Create GitHub issues from internal Error Tracker issues.
- Link existing GitHub issues by URL, `owner/repository#number`, `#number`, or bare number.
- Refresh linked GitHub issue title, state, URL, and metadata from GitHub.
- Unlink support that removes only the local external issue association.
- Generic external issue persistence for future providers.
- Dashboard UI for linked and unlinked external issues.
- GitHub integration configuration and diagnostics.
- Documentation for GitHub Issue Integration.

## [1.0.1] - 2026-05-05

### Fixed

- Added explicit read-only `GITHUB_TOKEN` permissions to the CI workflow.
- Resolved Dependabot alerts for Vite and esbuild used by the VitePress documentation build.
- Added npm overrides for Vite and esbuild while keeping the public PHP API unchanged.

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
