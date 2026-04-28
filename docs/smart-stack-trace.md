# Smart Stack Trace

The event detail page highlights frames that belong to your project and groups
consecutive framework, vendor, internal, or unknown frames into collapsed
non-project blocks.

This keeps the most useful application code visible while still allowing
framework/vendor details to be expanded when needed.

By default, project frames are detected from common Laravel paths and
namespaces:

```php
'stacktrace' => [
    'smart_grouping' => true,
    'project_paths' => [
        app_path(),
        base_path('routes'),
        base_path('database'),
        base_path('config'),
        base_path('packages'),
        base_path('modules'),
    ],
    'project_namespaces' => [
        'App\\',
        'Database\\',
    ],
    'non_project_paths' => [
        base_path('vendor'),
        base_path('storage/framework'),
        base_path('bootstrap/cache'),
    ],
    'collapse_non_project_frames' => true,
    'show_source_context' => true,
    'source_context_lines' => 5,
    'path_display' => env('ERROR_TRACKER_STACKTRACE_PATH_DISPLAY', 'relative'),
    'store_absolute_paths' => env('ERROR_TRACKER_STACKTRACE_STORE_ABSOLUTE_PATHS', false),
    'source_context' => [
        'enabled' => env('ERROR_TRACKER_SOURCE_CONTEXT_ENABLED', true),
        'lines_before' => 5,
        'lines_after' => 5,
        'max_frames' => 5,
        'project_only' => true,
        'fallback_to_throwing_frame' => true,
        'max_file_size_kb' => 512,
        'paths' => [
            app_path(),
            base_path('routes'),
            base_path('database'),
            base_path('config'),
            base_path('packages'),
            base_path('modules'),
        ],
        'excluded_paths' => [
            base_path('vendor'),
            storage_path(),
            base_path('bootstrap/cache'),
        ],
    ],
    'store_arguments' => false,
],
```

Function arguments are not stored or displayed by default for security.

Old traces that contain `args` or `arguments` are ignored by the presenter.
Source context is only read from configured `stacktrace.project_paths`, never
from `vendor` by default, and source lines containing tokens, passwords,
secrets, authorization values, cookies, or `x-api-key` are masked before
display.

## Path normalization and source context

Error Tracker normalizes stack trace paths by default before storing or
displaying them.

Absolute paths such as `/workspace/app/routes/web.php` are stored as
`routes/web.php`, which avoids leaking server directory structure in the
database or dashboard.

`stacktrace.path_display` accepts `relative`, `basename`, and `absolute`. The
default is `relative`.

If `path_display` is set to `absolute` but `stacktrace.store_absolute_paths` is
`false`, Error Tracker falls back to relative paths for safety.

Source context is enabled by default and stores a small snippet around eligible
project stack frames.

When an exception is thrown inside Laravel or another dependency, Error Tracker
marks the first project frame closest to the top of the stack as the application
frame and uses that frame for the event location and primary source context. The
original throwing frame is still kept in the stack trace and labeled separately
when it comes from the framework.

Source context is limited to configured project paths, skips excluded paths such
as `vendor`, `storage`, and `bootstrap/cache`, enforces a maximum file size, and
does not read `.env`.

Missing or unreadable files simply return no context, so the dashboard keeps
rendering. The dashboard escapes source lines when rendering them.

Available source context settings:

```php
'source_context' => [
    'enabled' => env('ERROR_TRACKER_SOURCE_CONTEXT_ENABLED', true),
    'lines_before' => 5,
    'lines_after' => 5,
    'max_frames' => 5,
    'project_only' => true,
    'fallback_to_throwing_frame' => true,
    'max_file_size_kb' => 512,
    'paths' => [
        app_path(),
        base_path('routes'),
        base_path('database'),
        base_path('config'),
        base_path('packages'),
        base_path('modules'),
    ],
    'excluded_paths' => [
        base_path('vendor'),
        storage_path(),
        base_path('bootstrap/cache'),
    ],
],
```
