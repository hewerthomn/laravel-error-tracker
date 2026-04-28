<?php

use Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer;
use Hewerthomn\ErrorTracker\Support\StackTrace\SourceContextReader;

beforeEach(function () {
    config([
        'error-tracker.stacktrace.show_source_context' => true,
        'error-tracker.stacktrace.source_context.enabled' => true,
        'error-tracker.stacktrace.source_context.lines_before' => 2,
        'error-tracker.stacktrace.source_context.lines_after' => 1,
        'error-tracker.stacktrace.source_context.max_file_size_kb' => 512,
        'error-tracker.stacktrace.source_context.project_only' => true,
        'error-tracker.stacktrace.source_context.excluded_paths' => [],
        'error-tracker.stacktrace.non_project_paths' => [],
    ]);
});

it('reads lines around the error line', function () {
    $root = sourceContextRoot('reads-lines');
    $file = makeSourceContextFile($root.'/app/Services/Foo.php', [
        '<?php',
        'line 2',
        'line 3',
        'throw new RuntimeException();',
        'line 5',
        'line 6',
    ]);

    config([
        'error-tracker.stacktrace.project_paths' => [$root.'/app'],
        'error-tracker.stacktrace.source_context.paths' => [$root.'/app'],
    ]);

    $context = sourceContextReader()->read($file, 4);

    expect($context)->toMatchArray([
        'start_line' => 2,
        'end_line' => 5,
        'error_line' => 4,
    ])
        ->and($context['lines'])->toHaveCount(4)
        ->and($context['lines'][2])->toMatchArray([
            'number' => 4,
            'code' => 'throw new RuntimeException();',
            'is_error_line' => true,
        ]);
});

it('returns null when the file does not exist', function () {
    config([
        'error-tracker.stacktrace.project_paths' => [sourceContextRoot('missing').'/app'],
        'error-tracker.stacktrace.source_context.paths' => [sourceContextRoot('missing').'/app'],
    ]);

    expect(sourceContextReader()->read(sourceContextRoot('missing').'/app/Missing.php', 1))->toBeNull();
});

it('does not read files outside allowed paths', function () {
    $root = sourceContextRoot('outside');
    $file = makeSourceContextFile($root.'/outside/Secret.php', ['<?php', '$secret = true;']);

    config([
        'error-tracker.stacktrace.project_paths' => [$root.'/app'],
        'error-tracker.stacktrace.source_context.paths' => [$root.'/app'],
    ]);

    expect(sourceContextReader()->read($file, 2))->toBeNull();
});

it('blocks path traversal', function () {
    $root = sourceContextRoot('traversal');

    makeSourceContextFile($root.'/secret.php', ['<?php', '$secret = true;']);
    config([
        'error-tracker.stacktrace.project_paths' => [$root.'/app'],
        'error-tracker.stacktrace.source_context.paths' => [$root.'/app'],
    ]);

    withTemporaryBasePath($root.'/app', function () {
        expect(sourceContextReader()->read('../secret.php', 2))->toBeNull();
    });
});

it('respects max file size', function () {
    $root = sourceContextRoot('max-size');
    $file = makeSourceContextFile($root.'/app/Large.php', ['<?php', str_repeat('a', 2048)]);

    config([
        'error-tracker.stacktrace.source_context.paths' => [$root.'/app'],
        'error-tracker.stacktrace.project_paths' => [$root.'/app'],
        'error-tracker.stacktrace.source_context.max_file_size_kb' => 1,
    ]);

    expect(sourceContextReader()->read($file, 2))->toBeNull();
});

it('respects excluded paths', function () {
    $root = sourceContextRoot('excluded');
    $file = makeSourceContextFile($root.'/app/Secrets/Token.php', ['<?php', '$token = true;']);

    config([
        'error-tracker.stacktrace.source_context.paths' => [$root.'/app'],
        'error-tracker.stacktrace.project_paths' => [$root.'/app'],
        'error-tracker.stacktrace.source_context.excluded_paths' => [$root.'/app/Secrets'],
    ]);

    expect(sourceContextReader()->read($file, 2))->toBeNull();
});

it('does not read vendor when project only is enabled', function () {
    $root = sourceContextRoot('vendor');
    $file = makeSourceContextFile($root.'/vendor/package/File.php', ['<?php', 'throw new RuntimeException();']);

    config([
        'error-tracker.stacktrace.source_context.paths' => [$root.'/vendor'],
        'error-tracker.stacktrace.project_paths' => [$root.'/vendor'],
        'error-tracker.stacktrace.source_context.project_only' => true,
    ]);

    withTemporaryBasePath($root, function () use ($file) {
        expect(sourceContextReader()->read($file, 2))->toBeNull();
    });
});

it('masks sensitive source lines', function () {
    $root = sourceContextRoot('sensitive-lines');
    $file = makeSourceContextFile($root.'/app/Services/Credentials.php', [
        '<?php',
        '$password = "plain-text-secret";',
        'throw new RuntimeException();',
    ]);

    config([
        'error-tracker.stacktrace.project_paths' => [$root.'/app'],
        'error-tracker.stacktrace.source_context.paths' => [$root.'/app'],
    ]);

    $context = sourceContextReader()->read($file, 2);

    expect($context)->not->toBeNull()
        ->and(json_encode($context))->not->toContain('plain-text-secret')
        ->and($context['lines'][1]['code'])->toBe('[REDACTED SENSITIVE SOURCE LINE]');
});

function sourceContextReader(): SourceContextReader
{
    return new SourceContextReader(new PathNormalizer);
}

function sourceContextRoot(string $name): string
{
    return sys_get_temp_dir().'/error-tracker-source-context-'.$name;
}

/**
 * @param  array<int, string>  $lines
 */
function makeSourceContextFile(string $path, array $lines): string
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

    return $path;
}
