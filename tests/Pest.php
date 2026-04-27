<?php

use Hewerthomn\ErrorTracker\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

function withTemporaryBasePath(string $basePath, Closure $callback): void
{
    $originalBasePath = base_path();

    app()->setBasePath($basePath);

    try {
        $callback();
    } finally {
        app()->setBasePath($originalBasePath);
    }
}
