<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Enables or disables the package globally.
    |
    */
    'enabled' => env('ERROR_TRACKER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Storage settings
    |--------------------------------------------------------------------------
    |
    | connection:
    |   Database connection used by the package models.
    |   Null means "use the application's default database connection".
    |
    | This allows multiple applications or environments to write into the same
    | tracker database when desired.
    |
    */
    'database' => [
        'connection' => env('ERROR_TRACKER_DB_CONNECTION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard route settings
    |--------------------------------------------------------------------------
    |
    | path:
    |   Public path used to access the dashboard.
    |
    | middleware:
    |   Middleware stack applied to all dashboard routes.
    |
    | gate:
    |   Authorization gate checked by the dashboard middleware.
    |
    */
    'route' => [
        'path' => env('ERROR_TRACKER_PATH', 'error-tracker'),
        'middleware' => ['web'],
        'gate' => 'viewErrorTracker',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard behavior
    |--------------------------------------------------------------------------
    |
    | title_prefix:
    |   Prefix used in the browser page title.
    |
    | show_environment_filter:
    |   "auto" shows the filter only when more than one distinct environment
    |   exists in the tracker storage.
    |
    | show_environment_badge:
    |   "auto" shows the environment badge only when more than one distinct
    |   environment exists in the tracker storage.
    |
    | show_environment_in_issue_header:
    |   Controls whether the issue header should display the environment badge.
    |   "auto" only shows it when multiple environments exist.
    |
    */
    'dashboard' => [
        'title_prefix' => 'Error Tracker',
        'show_environment_filter' => 'auto',
        'show_environment_badge' => 'auto',
        'show_environment_in_issue_header' => 'auto',

        /*
        |--------------------------------------------------------------------------
        | App navigation
        |--------------------------------------------------------------------------
        |
        | show_home_link:
        |   Shows a navigation shortcut back to the host application.
        |
        | app_home_url:
        |   URL used by the dashboard home shortcut.
        |   Defaults to "/" and can point to any application page.
        |
        | app_home_label:
        |   Accessible label for the home shortcut.
        |
        */
        'show_home_link' => true,
        'app_home_url' => env('ERROR_TRACKER_APP_HOME_URL', '/'),
        'app_home_label' => 'Back to app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Capture behavior
    |--------------------------------------------------------------------------
    |
    | environments:
    |   Only errors from these environments will be recorded.
    |
    | sample_rate:
    |   Sampling ratio from 0.0 to 1.0.
    |
    | store_headers:
    |   Stores sanitized request headers in the event payload.
    |
    | store_user:
    |   Stores authenticated user information when available.
    |
    | hash_ip:
    |   Stores a hash of the client IP instead of the raw IP.
    |
    | max_trace_frames:
    |   Limits how many stack trace frames are stored per event.
    |
    */
    'capture' => [
        'environments' => ['production', 'staging', 'local'],
        'sample_rate' => 1.0,
        'store_headers' => true,
        'store_user' => true,
        'hash_ip' => true,
        'max_trace_frames' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stack trace presentation
    |--------------------------------------------------------------------------
    */
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

        'store_arguments' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fingerprint normalization
    |--------------------------------------------------------------------------
    |
    | include_environment:
    |   When true, the current environment becomes part of the fingerprint.
    |   When false, the same exception can group into the same issue even if
    |   it came from different environments that share the same tracker storage.
    |
    */
    'fingerprint' => [
        'include_environment' => false,
        'normalize_ids' => true,
        'normalize_uuids' => true,
        'normalize_emails' => true,
        'normalize_tokens' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['mail'],
        'notify_on_new_issue' => true,
        'notify_on_reactivated' => true,
        'mail_to' => env('ERROR_TRACKER_MAIL_TO'),
        'slack_channel' => env('ERROR_TRACKER_SLACK_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom error page
    |--------------------------------------------------------------------------
    */
    'error_page' => [
        'enabled' => env('ERROR_TRACKER_ERROR_PAGE_ENABLED', true),
        'only_html_requests' => true,
        'only_when_debug_disabled' => true,
        'title' => 'Something went wrong',
        'message' => 'An unexpected error occurred. Please try again in a moment.',
        'show_reference' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feedback form
    |--------------------------------------------------------------------------
    */
    'feedback' => [
        'enabled' => env('ERROR_TRACKER_FEEDBACK_ENABLED', false),
        'allow_guest' => true,
        'only_production' => false,
        'rate_limit' => '5,1',
        'collect_name' => true,
        'collect_email' => true,
        // Authenticated feedback always uses request()->user() for name/email when available.
        'prefill_authenticated_user' => true,
        // Kept for compatibility; authenticated identity fields are rendered readonly in the MVP UI.
        'lock_authenticated_user_fields' => true,
        'max_length' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data retention
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'events_days' => 30,
        'resolved_issues_days' => 90,
        'delete_feedback_after_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto resolve
    |--------------------------------------------------------------------------
    |
    | Automatically resolves stale open issues that have not received new
    | events after the configured number of days. Disabled by default.
    |
    */
    'auto_resolve' => [
        'enabled' => env('ERROR_TRACKER_AUTO_RESOLVE_ENABLED', false),
        'after_days' => env('ERROR_TRACKER_AUTO_RESOLVE_AFTER_DAYS', 14),
        'statuses' => ['open'],
        'levels' => ['warning', 'error'],
        'environments' => null,
        'reason' => 'Automatically resolved after :days days without new events.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive data redaction
    |--------------------------------------------------------------------------
    */
    'redaction' => [
        'headers' => ['authorization', 'cookie', 'x-api-key'],
        'request_fields' => ['password', 'password_confirmation', 'token', 'secret'],
    ],
];
