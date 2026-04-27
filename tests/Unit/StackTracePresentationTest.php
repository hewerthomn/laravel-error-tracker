<?php

use Hewerthomn\ErrorTracker\Support\SourceContextReader;
use Hewerthomn\ErrorTracker\Support\StackFrameClassifier;
use Hewerthomn\ErrorTracker\Support\StackTracePresenter;

beforeEach(function () {
    $root = stackTracePresentationRoot();

    config([
        'error-tracker.stacktrace.smart_grouping' => true,
        'error-tracker.stacktrace.project_paths' => [
            $root.'/app',
            $root.'/routes',
            $root.'/database',
        ],
        'error-tracker.stacktrace.project_namespaces' => [
            'App\\',
            'Database\\',
        ],
        'error-tracker.stacktrace.non_project_paths' => [
            $root.'/vendor',
            $root.'/storage/framework',
            $root.'/bootstrap/cache',
        ],
        'error-tracker.stacktrace.collapse_non_project_frames' => true,
        'error-tracker.stacktrace.show_source_context' => true,
        'error-tracker.stacktrace.source_context_lines' => 1,
    ]);
});

it('classifies app frames as project', function () {
    $classifier = new StackFrameClassifier;

    expect($classifier->classify([
        'file' => stackTracePresentationRoot().'/app/Services/FooService.php',
        'class' => 'App\\Services\\FooService',
        'function' => 'handle',
    ]))->toBe('project');
});

it('classifies route frames as project', function () {
    $classifier = new StackFrameClassifier;

    expect($classifier->classify([
        'file' => stackTracePresentationRoot().'/routes/web.php',
        'function' => '{closure}',
    ]))->toBe('project');
});

it('classifies database frames as project', function () {
    $classifier = new StackFrameClassifier;

    expect($classifier->classify([
        'file' => stackTracePresentationRoot().'/database/migrations/create_users_table.php',
        'class' => 'Database\\Migrations\\CreateUsersTable',
    ]))->toBe('project');
});

it('classifies vendor frames as vendor', function () {
    $classifier = new StackFrameClassifier;

    expect($classifier->classify([
        'file' => stackTracePresentationRoot().'/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
        'class' => 'Illuminate\\Routing\\Router',
    ]))->toBe('vendor');
});

it('classifies storage framework frames as framework', function () {
    $classifier = new StackFrameClassifier;

    expect($classifier->classify([
        'file' => stackTracePresentationRoot().'/storage/framework/views/view.php',
        'function' => 'include',
    ]))->toBe('framework');
});

it('groups consecutive non project frames', function () {
    $presenter = stackTracePresentationPresenter();
    $root = stackTracePresentationRoot();

    $trace = [
        ['file' => $root.'/app/Services/FooService.php', 'line' => 10, 'class' => 'App\\Services\\FooService', 'function' => 'handle'],
        ['file' => $root.'/vendor/laravel/framework/src/Illuminate/Routing/Router.php', 'line' => 20, 'class' => 'Illuminate\\Routing\\Router', 'function' => 'dispatch'],
        ['file' => $root.'/storage/framework/views/view.php', 'line' => 30, 'function' => 'include'],
        ['file' => $root.'/app/Http/Controllers/HomeController.php', 'line' => 40, 'class' => 'App\\Http\\Controllers\\HomeController', 'function' => '__invoke'],
    ];

    $result = $presenter->present($trace);

    expect($result['frames'])->toHaveCount(3)
        ->and($result['frames'][0]['type'])->toBe('frame')
        ->and($result['frames'][1]['type'])->toBe('group')
        ->and($result['frames'][1]['count'])->toBe(2)
        ->and($result['frames'][2]['type'])->toBe('frame');
});

it('does not group project frames', function () {
    $presenter = stackTracePresentationPresenter();
    $root = stackTracePresentationRoot();

    $result = $presenter->present([
        ['file' => $root.'/app/Services/FooService.php', 'line' => 10, 'class' => 'App\\Services\\FooService', 'function' => 'handle'],
        ['file' => $root.'/routes/web.php', 'line' => 20, 'function' => '{closure}'],
    ]);

    expect($result['frames'])->toHaveCount(2)
        ->and($result['frames'][0]['type'])->toBe('frame')
        ->and($result['frames'][1]['type'])->toBe('frame');
});

it('ignores frame arguments by default', function () {
    $presenter = stackTracePresentationPresenter();

    $result = $presenter->present([
        [
            'file' => stackTracePresentationRoot().'/app/Services/FooService.php',
            'line' => 10,
            'class' => 'App\\Services\\FooService',
            'function' => 'handle',
            'args' => ['password' => 'secret-password'],
        ],
    ]);

    expect(json_encode($result))->not->toContain('secret-password')
        ->and($result['frames'][0])->not->toHaveKey('args');
});

it('generates relative files', function () {
    $presenter = stackTracePresentationPresenter();
    $root = stackTracePresentationRoot();

    $result = $presenter->present([
        ['file' => $root.'/app/Services/FooService.php', 'line' => 10, 'class' => 'App\\Services\\FooService', 'function' => 'handle'],
    ]);

    expect($result['frames'][0]['relative_file'])->toBe('app/Services/FooService.php');
});

it('only reads source context from configured project paths', function () {
    $root = stackTracePresentationRoot();
    $projectFile = $root.'/app/Services/ContextService.php';
    $outsideFile = $root.'/outside/Secret.php';

    @mkdir(dirname($projectFile), 0777, true);
    @mkdir(dirname($outsideFile), 0777, true);

    file_put_contents($projectFile, "<?php\nfinal class ContextService\n{\n    public function handle(): void {}\n}\n");
    file_put_contents($outsideFile, "<?php\n\$secret = 'do-not-read';\n");

    $reader = new SourceContextReader(new StackFrameClassifier);

    expect($reader->read(['file' => $projectFile, 'line' => 3]))->not->toBeNull()
        ->and($reader->read(['file' => $outsideFile, 'line' => 2]))->toBeNull();
});

it('does not break with empty or invalid traces', function () {
    $presenter = stackTracePresentationPresenter();

    expect($presenter->present(null))
        ->toMatchArray(['frames' => [], 'first_project_frame' => null, 'has_frames' => false])
        ->and($presenter->present([]))
        ->toMatchArray(['frames' => [], 'first_project_frame' => null, 'has_frames' => false])
        ->and($presenter->present(['not-a-frame']))
        ->toMatchArray(['frames' => [], 'first_project_frame' => null, 'has_frames' => false]);
});

function stackTracePresentationRoot(): string
{
    return sys_get_temp_dir().'/error-tracker-stacktrace-tests';
}

function stackTracePresentationPresenter(): StackTracePresenter
{
    $classifier = new StackFrameClassifier;

    return new StackTracePresenter($classifier, new SourceContextReader($classifier));
}
