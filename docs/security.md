# Security

## Redaction

Review redaction rules before enabling the package in production.

Secrets are never displayed raw on the configuration diagnostics page.
Notification recipients, Slack webhook values, tokens, secrets, passwords,
authorization headers, cookies, and API keys are rendered as `configured` or
`not configured`.

## Stack traces

Function arguments are not stored or displayed by default for security.

Old traces that contain `args` or `arguments` are ignored by the presenter.

Source context is only read from configured `stacktrace.project_paths`, never
from `vendor` by default, and source lines containing tokens, passwords,
secrets, authorization values, cookies, or `x-api-key` are masked before
display.

## Source context

Source context is limited to configured project paths, skips excluded paths such
as `vendor`, `storage`, and `bootstrap/cache`, enforces a maximum file size, and
does not read `.env`.

Missing or unreadable files simply return no context, so the dashboard keeps
rendering. The dashboard escapes source lines when rendering them.

## Feedback

Authenticated users see name and email prefilled from their signed-in account as
readonly fields. This is only a usability hint: the backend always uses
`request()->user()` as the source of truth when available and ignores submitted
name/email values for signed-in users.
