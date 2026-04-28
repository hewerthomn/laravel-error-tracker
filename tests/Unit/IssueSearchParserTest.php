<?php

use Hewerthomn\ErrorTracker\Support\Dashboard\IssueSearchParser;

it('parses status operator', function () {
    expect(issueSearchParser()->parse('status:open')['statuses'])->toBe(['open']);
});

it('parses level operator', function () {
    expect(issueSearchParser()->parse('level:error')['levels'])->toBe(['error']);
});

it('parses environment operator', function () {
    expect(issueSearchParser()->parse('env:production')['environments'])->toBe(['production'])
        ->and(issueSearchParser()->parse('environment:staging')['environments'])->toBe(['staging']);
});

it('parses exception class operator', function () {
    expect(issueSearchParser()->parse('class:QueryException')['exception_class'])->toBe('QueryException')
        ->and(issueSearchParser()->parse('exception:RuntimeException')['exception_class'])->toBe('RuntimeException');
});

it('parses route operator', function () {
    expect(issueSearchParser()->parse('route:users.store')['route'])->toBe('users.store');
});

it('parses file operator', function () {
    expect(issueSearchParser()->parse('file:UserController.php')['file'])->toBe('UserController.php');
});

it('parses has feedback operator', function () {
    expect(issueSearchParser()->parse('has:feedback')['has_feedback'])->toBeTrue();
});

it('keeps free text', function () {
    expect(issueSearchParser()->parse('checkout timeout')['text'])->toBe('checkout timeout');
});

it('combines free text with operators', function () {
    $parsed = issueSearchParser()->parse('checkout status:open level:error');

    expect($parsed['text'])->toBe('checkout')
        ->and($parsed['statuses'])->toBe(['open'])
        ->and($parsed['levels'])->toBe(['error']);
});

it('supports quoted operator values', function () {
    $parsed = issueSearchParser()->parse('message:"checkout timeout" route:orders.store');

    expect($parsed['message'])->toBe('checkout timeout')
        ->and($parsed['route'])->toBe('orders.store');
});

it('ignores invalid operator values safely', function () {
    $parsed = issueSearchParser()->parse('status:deleted level:panic resolved:robot status_code:999 unknown:value');

    expect($parsed['statuses'])->toBe([])
        ->and($parsed['levels'])->toBe([])
        ->and($parsed['resolved_by_type'])->toBeNull()
        ->and($parsed['status_code'])->toBeNull()
        ->and($parsed['text'])->toBe('unknown:value');
});

function issueSearchParser(): IssueSearchParser
{
    return new IssueSearchParser;
}
