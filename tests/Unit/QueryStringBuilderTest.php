<?php

use Hewerthomn\ErrorTracker\Support\Dashboard\QueryStringBuilder;
use Illuminate\Http\Request;

it('preserves the query string and replaces only the requested parameter', function () {
    $request = Request::create('/error-tracker', 'GET', [
        'period' => '24h',
        'status' => 'open',
        'level' => 'error',
        'search' => 'oracle',
    ]);

    $url = QueryStringBuilder::fromRequest($request, '/error-tracker')
        ->url(['status' => 'resolved']);

    expect($url)->toBe('/error-tracker?period=24h&status=resolved&level=error&search=oracle');
});

it('removes page when changing filters', function () {
    $request = Request::create('/error-tracker', 'GET', [
        'period' => '7d',
        'environment' => 'production',
        'search' => 'checkout',
        'page' => 3,
    ]);

    $url = QueryStringBuilder::fromRequest($request, '/error-tracker')
        ->url(['sort' => 'frequent']);

    expect($url)->toBe('/error-tracker?period=7d&environment=production&search=checkout&sort=frequent');
});

it('removes parameters when values are null or all', function () {
    $request = Request::create('/error-tracker', 'GET', [
        'period' => '24h',
        'status' => 'open',
        'level' => 'error',
        'page' => 2,
    ]);

    $url = QueryStringBuilder::fromRequest($request, '/error-tracker')
        ->url([
            'status' => null,
            'level' => 'all',
        ]);

    expect($url)->toBe('/error-tracker?period=24h');
});
