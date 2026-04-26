<?php

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Issue;

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
}

function throwFromStableLocation(int $id): void
{
    throw new RuntimeException("User {$id} not found");
}