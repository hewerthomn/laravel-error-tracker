<?php

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Support\StackTracePresenter;

it('records and groups repeated exceptions into the same issue', function () {
    $action = app(RecordThrowableAction::class);

    $first = recordUserNotFound($action, 12345);
    $second = recordUserNotFound($action, 67890);

    expect($first)->not->toBeNull()
        ->and($second)->not->toBeNull()
        ->and(Issue::query()->count())->toBe(1)
        ->and(Event::query()->count())->toBe(2)
        ->and($first->issue->id)->toBe($second->issue->id)
        ->and($first->issueWasCreated)->toBeTrue()
        ->and($second->issueWasCreated)->toBeFalse();

    $issue = Issue::query()->first();

    expect($issue)->not->toBeNull()
        ->and($issue->total_events)->toBe(2)
        ->and($issue->events()->count())->toBe(2)
        ->and($issue->message_sample)->toContain('User');
});

it('stores event and trace paths as relative paths with source context for project frames', function () {
    $root = errorTrackerCaptureRoot('project');
    $throwable = makeThrowableFromGeneratedFile($root, 'routes/web.php', false);

    withConfiguredCaptureRoot($root, function () use ($throwable, $root) {
        $result = app(RecordThrowableAction::class)->handle($throwable, ['level' => 'error']);

        if ($result === null) {
            $this->fail('Expected the throwable to be recorded.');
        }

        $event = $result->event->fresh();
        $trace = $event->trace_json ?? [];
        $projectFrame = collect($trace)->first(
            fn (array $frame): bool => str_starts_with((string) ($frame['file'] ?? ''), 'routes/')
        );

        expect($event)->not->toBeNull()
            ->and($event->file)->toBe('routes/web.php')
            ->and(json_encode($trace))->not->toContain($root)
            ->and($projectFrame)->not->toBeNull()
            ->and($projectFrame['file'])->toBe('routes/web.php')
            ->and($projectFrame)->toHaveKey('source_context')
            ->and($projectFrame)->not->toHaveKey('args')
            ->and(json_encode($trace))->not->toContain('top-secret');
    });
});

it('does not include source context for vendor frames by default', function () {
    $root = errorTrackerCaptureRoot('vendor');
    $throwable = makeThrowableFromGeneratedFile($root, 'vendor/laravel/framework/src/Fake.php', true);

    withConfiguredCaptureRoot($root, function () use ($throwable) {
        $result = app(RecordThrowableAction::class)->handle($throwable, ['level' => 'error']);

        if ($result === null) {
            $this->fail('Expected the throwable to be recorded.');
        }

        $trace = $result->event->fresh()->trace_json ?? [];
        $vendorFrame = collect($trace)->first(
            fn (array $frame): bool => str_starts_with((string) ($frame['file'] ?? ''), 'vendor/')
        );

        expect($vendorFrame)->not->toBeNull()
            ->and($vendorFrame)->not->toHaveKey('source_context');
    });
});

it('uses the first project frame as culprit when an exception is thrown in framework code', function () {
    $root = errorTrackerCaptureRoot('framework-culprit');
    $throwable = makeFrameworkThrowableCalledFromApplication($root);

    withConfiguredCaptureRoot($root, function () use ($throwable) {
        $result = app(RecordThrowableAction::class)->handle($throwable, ['level' => 'error']);

        if ($result === null) {
            $this->fail('Expected the throwable to be recorded.');
        }

        $event = $result->event->fresh(['issue', 'feedback']);
        $trace = $event->trace_json ?? [];
        $throwingFrame = collect($trace)->first(fn (array $frame): bool => (bool) ($frame['is_throwing_frame'] ?? false));
        $culpritFrame = collect($trace)->first(fn (array $frame): bool => (bool) ($frame['is_culprit'] ?? false));
        $stackTrace = app(StackTracePresenter::class)->present($trace);
        /** @var view-string $view */
        $view = 'error-tracker::dashboard.event-show';
        $html = view($view, [
            'event' => $event,
            'stackTrace' => $stackTrace,
        ])->render();

        expect($event->file)->toBe('app/Http/Controllers/ReportController.php')
            ->and($throwingFrame)->not->toBeNull()
            ->and($throwingFrame['file'])->toBe('vendor/laravel/framework/src/Illuminate/Database/Connection.php')
            ->and($throwingFrame['classification'])->toBe('framework')
            ->and($throwingFrame['is_culprit'])->toBeFalse()
            ->and($throwingFrame)->not->toHaveKey('source_context')
            ->and($culpritFrame)->not->toBeNull()
            ->and($culpritFrame['file'])->toBe('app/Http/Controllers/ReportController.php')
            ->and($culpritFrame['classification'])->toBe('project')
            ->and($culpritFrame)->toHaveKey('source_context')
            ->and($html)->toContain('Application frame')
            ->and($html)->toContain('Exception thrown in framework')
            ->and($html)->toContain('app/Http/Controllers/ReportController.php');
    });
});

it('renders normalized paths and source context on the event detail view', function () {
    $root = errorTrackerCaptureRoot('view');
    $throwable = makeThrowableFromGeneratedFile($root, 'routes/web.php', false);

    withConfiguredCaptureRoot($root, function () use ($throwable, $root) {
        $result = app(RecordThrowableAction::class)->handle($throwable, ['level' => 'error']);

        if ($result === null) {
            $this->fail('Expected the throwable to be recorded.');
        }

        $event = $result->event->fresh(['issue', 'feedback']);
        $stackTrace = app(StackTracePresenter::class)->present($event->trace_json);
        /** @var view-string $view */
        $view = 'error-tracker::dashboard.event-show';
        $html = view($view, [
            'event' => $event,
            'stackTrace' => $stackTrace,
        ])->render();

        expect($html)->toContain('routes/web.php')
            ->and($html)->not->toContain($root)
            ->and($html)->toContain('throw new RuntimeException')
            ->and($html)->toContain('is-highlighted');
    });
});

function recordUserNotFound(RecordThrowableAction $action, int $id)
{
    return $action->handle(makeUserNotFoundException($id), [
        'level' => 'error',
    ]);
}

function makeUserNotFoundException(int $id): Throwable
{
    try {
        throwFromStableLocation($id);
    } catch (Throwable $e) {
        return $e;
    }

    throw new RuntimeException('Unable to create test exception.');
}

function throwFromStableLocation(int $id): void
{
    throw new RuntimeException("User {$id} not found");
}

function errorTrackerCaptureRoot(string $name): string
{
    return sys_get_temp_dir().'/error-tracker-capture-'.$name.'-'.uniqid();
}

function withConfiguredCaptureRoot(string $root, Closure $callback): void
{
    withTemporaryBasePath($root, function () use ($root, $callback) {
        config([
            'error-tracker.stacktrace.path_display' => 'relative',
            'error-tracker.stacktrace.store_absolute_paths' => false,
            'error-tracker.stacktrace.source_context.enabled' => true,
            'error-tracker.stacktrace.source_context.lines_before' => 5,
            'error-tracker.stacktrace.source_context.lines_after' => 5,
            'error-tracker.stacktrace.source_context.max_frames' => 5,
            'error-tracker.stacktrace.source_context.project_only' => true,
            'error-tracker.stacktrace.source_context.paths' => [
                $root.'/app',
                $root.'/routes',
                $root.'/database',
                $root.'/packages',
                $root.'/modules',
            ],
            'error-tracker.stacktrace.source_context.excluded_paths' => [
                $root.'/vendor',
                $root.'/storage',
                $root.'/bootstrap/cache',
            ],
            'error-tracker.stacktrace.project_paths' => [
                $root.'/app',
                $root.'/routes',
                $root.'/database',
                $root.'/packages',
                $root.'/modules',
            ],
            'error-tracker.stacktrace.non_project_paths' => [
                $root.'/vendor',
                $root.'/storage/framework',
                $root.'/bootstrap/cache',
            ],
        ]);

        $callback();
    });
}

function makeThrowableFromGeneratedFile(string $root, string $relativePath, bool $vendor): Throwable
{
    $file = $root.'/'.$relativePath;
    $function = 'error_tracker_generated_'.str_replace('.', '_', uniqid('', true));

    if (! is_dir(dirname($file))) {
        mkdir(dirname($file), 0777, true);
    }

    $message = $vendor ? 'Vendor source context failure' : 'Route source context failure';

    file_put_contents($file, <<<PHP
<?php

function {$function}(\$password): void
{
    throw new RuntimeException('{$message}');
}

{$function}(\$GLOBALS['error_tracker_test_secret']);
PHP);

    $GLOBALS['error_tracker_test_secret'] = 'top-secret';

    try {
        require $file;
    } catch (Throwable $throwable) {
        unset($GLOBALS['error_tracker_test_secret']);

        return $throwable;
    }

    unset($GLOBALS['error_tracker_test_secret']);

    throw new RuntimeException('Unable to create generated test exception.');
}

function makeFrameworkThrowableCalledFromApplication(string $root): Throwable
{
    $frameworkFile = $root.'/vendor/laravel/framework/src/Illuminate/Database/Connection.php';
    $applicationFile = $root.'/app/Http/Controllers/ReportController.php';
    $frameworkFunction = 'error_tracker_framework_'.str_replace('.', '_', uniqid('', true));
    $applicationFunction = 'error_tracker_application_'.str_replace('.', '_', uniqid('', true));

    if (! is_dir(dirname($frameworkFile))) {
        mkdir(dirname($frameworkFile), 0777, true);
    }

    if (! is_dir(dirname($applicationFile))) {
        mkdir(dirname($applicationFile), 0777, true);
    }

    file_put_contents($frameworkFile, <<<PHP
<?php

function {$frameworkFunction}(): void
{
    throw new RuntimeException('Framework originated failure');
}
PHP);

    require $frameworkFile;

    file_put_contents($applicationFile, <<<PHP
<?php

function {$applicationFunction}(): void
{
    {$frameworkFunction}();
}

{$applicationFunction}();
PHP);

    try {
        require $applicationFile;
    } catch (Throwable $throwable) {
        return $throwable;
    }

    throw new RuntimeException('Unable to create framework-originated test exception.');
}
