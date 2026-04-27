<?php

use Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer;

it('normalizes route paths relative to the project base path', function () {
    withTemporaryBasePath('/workspace/error-tracker-sandbox', function () {
        expect((new PathNormalizer)->normalize('/workspace/error-tracker-sandbox/routes/web.php'))
            ->toBe('routes/web.php');
    });
});

it('normalizes app controller paths relative to the project base path', function () {
    withTemporaryBasePath('/workspace/error-tracker-sandbox', function () {
        expect((new PathNormalizer)->normalize('/workspace/error-tracker-sandbox/app/Http/Controllers/FooController.php'))
            ->toBe('app/Http/Controllers/FooController.php');
    });
});

it('normalizes vendor paths relative to the project base path', function () {
    withTemporaryBasePath('/workspace/error-tracker-sandbox', function () {
        expect((new PathNormalizer)->normalize('/workspace/error-tracker-sandbox/vendor/laravel/framework/src/Illuminate/Routing/Router.php'))
            ->toBe('vendor/laravel/framework/src/Illuminate/Routing/Router.php');
    });
});

it('supports windows separators', function () {
    withTemporaryBasePath('C:/workspace/error-tracker-sandbox', function () {
        expect((new PathNormalizer)->normalize('C:\\workspace\\error-tracker-sandbox\\routes\\web.php'))
            ->toBe('routes/web.php');
    });
});

it('returns null for null paths', function () {
    expect((new PathNormalizer)->normalize(null))->toBeNull();
});

it('supports basename display mode', function () {
    config(['error-tracker.stacktrace.path_display' => 'basename']);

    withTemporaryBasePath('/workspace/error-tracker-sandbox', function () {
        expect((new PathNormalizer)->normalize('/workspace/error-tracker-sandbox/routes/web.php'))
            ->toBe('web.php');
    });
});

it('does not return absolute paths in absolute mode when storage is disabled', function () {
    config([
        'error-tracker.stacktrace.path_display' => 'absolute',
        'error-tracker.stacktrace.store_absolute_paths' => false,
    ]);

    withTemporaryBasePath('/workspace/error-tracker-sandbox', function () {
        expect((new PathNormalizer)->normalize('/workspace/error-tracker-sandbox/routes/web.php'))
            ->toBe('routes/web.php');
    });
});
