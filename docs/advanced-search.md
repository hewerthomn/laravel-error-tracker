# Advanced Search

The dashboard index uses GET query parameters, so filtered views can be shared
or bookmarked:

```text
/error-tracker?q=checkout%20status:open%20level:error&period=24h&sort=last_seen_at&direction=desc
```

The search box accepts free text plus operators.

Free text searches issue title, exception class, message sample, fingerprint,
status, level, environment, and resolution metadata. Event and feedback tables
are only queried when an operator requires them.

## Supported operators

```text
status:open
status:resolved
level:error
level:critical
env:production
environment:staging
class:QueryException
exception:QueryException
message:timeout
fingerprint:abc123
route:users.store
path:/api/users
url:example.com
file:UserController.php
user:123
status_code:500
resolved:auto
resolved:manual
has:feedback
```

Values with spaces can be quoted:

```text
message:"checkout timeout" status:open
```

The visual filters cover status, level, environment, period, resolution type,
feedback presence, sort, and direction.

Active filters are shown as chips, and the Clear filters action returns to
`/error-tracker` without query parameters. Inputs are validated against allowed
values and applied through Eloquent query builder methods rather than raw SQL.
