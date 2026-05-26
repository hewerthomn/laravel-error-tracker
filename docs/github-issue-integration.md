# GitHub Issue Integration

## Overview

The GitHub Issue Integration connects an internal Error Tracker issue to a
GitHub issue. It is optional, disabled by default, and does not change exception
capture behavior.

The MVP supports:

- creating a GitHub issue from an Error Tracker issue;
- linking an existing GitHub issue;
- showing the linked issue on the detail page and as a compact badge in lists;
- refreshing title, state, URL, and metadata from GitHub;
- unlinking only the local association.

The persistence layer uses generic external issue fields so future providers can
be added without adding provider-specific columns to `error_tracker_issues`.

## Requirements

- Published package migrations, including `error_tracker_external_issues`.
- A GitHub personal access token or fine-grained token with Issues read/write
  access to the target repository.
- The `viewErrorTracker` gate configured for dashboard users.

## Token permissions

Use the least privilege token that can read and write issues for the configured
repository. For fine-grained GitHub tokens, grant:

```text
Repository permissions: Issues read and write
```

The token is never displayed raw in the dashboard, diagnostics, doctor output,
logs, or user-facing error messages.

## Configuration

```env
ERROR_TRACKER_GITHUB_ENABLED=false
ERROR_TRACKER_GITHUB_TOKEN=
ERROR_TRACKER_GITHUB_REPOSITORY=owner/repository
ERROR_TRACKER_GITHUB_LABELS=bug,error-tracker
ERROR_TRACKER_GITHUB_ASSIGNEES=
ERROR_TRACKER_GITHUB_TITLE_TEMPLATE="[{environment}] {exception_class} in {location}"
ERROR_TRACKER_GITHUB_INCLUDE_STACK_TRACE=true
ERROR_TRACKER_GITHUB_MAX_STACK_FRAMES=15
ERROR_TRACKER_GITHUB_INCLUDE_CONTEXT=false
ERROR_TRACKER_GITHUB_INCLUDE_SOURCE_CONTEXT=false
```

Keep `ERROR_TRACKER_GITHUB_ENABLED=false` until the token and repository are
configured.

## Create a GitHub issue

Open an Error Tracker issue detail page and use **Create GitHub issue**. The
package creates one GitHub issue in the configured repository and stores the
local link in `error_tracker_external_issues`.

The generated GitHub body includes safe summary data by default:

- internal issue link when route generation is available;
- exception class, level, environment, first/last seen, occurrences;
- route or path;
- location;
- safe exception message;
- application stack frames limited by `ERROR_TRACKER_GITHUB_MAX_STACK_FRAMES`.

It does not include request headers, cookies, Authorization values, session
data, full context JSON, or full source context by default.

## Link an existing issue

Use **Link existing issue** on the issue detail page. Accepted formats:

```text
https://github.com/owner/repository/issues/123
owner/repository#123
#123
123
```

When only `#123` or `123` is provided, the configured
`ERROR_TRACKER_GITHUB_REPOSITORY` is used. The package validates the issue via
GitHub API before saving the local association.

## Refresh status from GitHub

Use **Refresh from GitHub** to read the current linked GitHub issue title,
state, URL, metadata, and update `last_synced_at`.

Refreshing status reads the current GitHub issue state and updates the local
external issue link. It does not modify GitHub and does not change the internal
Error Tracker issue status.

## Unlink

Use **Unlink** to remove only the local association. This does not close,
reopen, edit, comment on, or otherwise modify the GitHub issue.

## Security notes

- All routes use the same dashboard middleware and `viewErrorTracker` gate.
- CSRF protection is the normal Laravel web protection.
- The token is always masked in diagnostics.
- Link input only accepts GitHub issue URLs or issue numbers in the supported
  formats.
- The saved URL comes from the GitHub API response, not arbitrary user input.
- External API failures are shown as friendly dashboard messages and do not
  affect exception capture.
- GitHub issues are never created automatically.
- GitHub issues are never closed or reopened automatically.

## Troubleshooting

Run diagnostics:

```bash
php artisan error-tracker:doctor
```

Common cases:

- `GitHub issue integration is disabled`: set
  `ERROR_TRACKER_GITHUB_ENABLED=true` after configuring token and repository.
- `GitHub token is not configured`: set `ERROR_TRACKER_GITHUB_TOKEN`.
- `GitHub repository must be configured as owner/repository`: set
  `ERROR_TRACKER_GITHUB_REPOSITORY=owner/repository`.
- `GitHub token is invalid or expired`: rotate the token.
- `GitHub token does not have permission`: grant Issues read/write access.
- `GitHub repository or issue was not found`: confirm the repository, issue
  number, and token access.
- `GitHub issues are disabled or unavailable`: enable Issues in the repository.
- `GitHub rejected the issue payload`: verify labels and assignees exist in the
  repository.
