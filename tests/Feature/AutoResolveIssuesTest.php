<?php

use Hewerthomn\ErrorTracker\Actions\AutoResolveIssuesAction;
use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Services\IssueStatusService;
use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Support\Str;

it('has auto resolve disabled by default', function () {
    expect(config('error-tracker.auto_resolve.enabled'))->toBeFalse();
});

it('does not alter issues from the command when auto resolve is disabled', function () {
    $issue = createAutoResolveIssue([
        'last_seen_at' => now()->subDays(30),
    ]);

    /** @var TestCase $this */
    $this->artisan('error-tracker:auto-resolve')
        ->expectsOutput('Starting Error Tracker auto resolve...')
        ->expectsOutput('Dry run: no')
        ->expectsOutput('Auto resolve is disabled.')
        ->assertExitCode(0);

    $issue = $issue->fresh();

    expect($issue->status)->toBe('open')
        ->and($issue->resolved_at)->toBeNull()
        ->and($issue->resolved_by_type)->toBeNull()
        ->and($issue->resolved_reason)->toBeNull();
});

it('finds eligible issues during dry run without changing status', function () {
    config(['error-tracker.auto_resolve.enabled' => true]);

    $issue = createAutoResolveIssue([
        'last_seen_at' => now()->subDays(30),
    ]);

    /** @var TestCase $this */
    $this->artisan('error-tracker:auto-resolve --dry-run')
        ->expectsOutput('Eligible issues: 1')
        ->expectsOutput('Dry run finished. No issues were changed.')
        ->assertExitCode(0);

    expect($issue->fresh()->status)->toBe('open')
        ->and($issue->fresh()->resolved_at)->toBeNull();
});

it('automatically resolves old open issues when enabled', function () {
    config(['error-tracker.auto_resolve.enabled' => true]);

    $issue = createAutoResolveIssue([
        'last_seen_at' => now()->subDays(30),
    ]);

    $result = app(AutoResolveIssuesAction::class)->handle();
    $issue = $issue->fresh();

    expect($result['found'])->toBe(1)
        ->and($result['resolved'])->toBe(1)
        ->and($issue->status)->toBe('resolved')
        ->and($issue->resolved_at)->not->toBeNull()
        ->and($issue->resolved_by_type)->toBe('auto')
        ->and($issue->resolved_reason)->toBe('Automatically resolved after 14 days without new events.');
});

it('does not resolve recent open issues', function () {
    config(['error-tracker.auto_resolve.enabled' => true]);

    $issue = createAutoResolveIssue([
        'last_seen_at' => now()->subDays(2),
    ]);

    $result = app(AutoResolveIssuesAction::class)->handle();

    expect($result['found'])->toBe(0)
        ->and($result['resolved'])->toBe(0)
        ->and($issue->fresh()->status)->toBe('open');
});

it('does not resolve critical issues by default', function () {
    config(['error-tracker.auto_resolve.enabled' => true]);

    $issue = createAutoResolveIssue([
        'level' => 'critical',
        'last_seen_at' => now()->subDays(30),
    ]);

    $result = app(AutoResolveIssuesAction::class)->handle();

    expect($result['found'])->toBe(0)
        ->and($issue->fresh()->status)->toBe('open');
});

it('does not alter ignored muted or already resolved issues', function () {
    config(['error-tracker.auto_resolve.enabled' => true]);

    $ignored = createAutoResolveIssue([
        'status' => 'ignored',
        'last_seen_at' => now()->subDays(30),
        'ignored_at' => now()->subDays(20),
    ]);

    $muted = createAutoResolveIssue([
        'status' => 'muted',
        'last_seen_at' => now()->subDays(30),
        'muted_until' => now()->addDays(3),
        'mute_reason' => 'Noise',
    ]);

    $resolved = createAutoResolveIssue([
        'status' => 'resolved',
        'last_seen_at' => now()->subDays(30),
        'resolved_at' => now()->subDays(15),
        'resolved_by_type' => 'manual',
        'resolved_reason' => 'Fixed',
    ]);

    $result = app(AutoResolveIssuesAction::class)->handle();

    expect($result['found'])->toBe(0)
        ->and($ignored->fresh()->status)->toBe('ignored')
        ->and($muted->fresh()->status)->toBe('muted')
        ->and($resolved->fresh()->status)->toBe('resolved')
        ->and($resolved->fresh()->resolved_by_type)->toBe('manual')
        ->and($resolved->fresh()->resolved_reason)->toBe('Fixed');
});

it('uses the days option as a temporary auto resolve override', function () {
    config(['error-tracker.auto_resolve.enabled' => true]);

    $issue = createAutoResolveIssue([
        'last_seen_at' => now()->subDays(5),
    ]);

    /** @var TestCase $this */
    $this->artisan('error-tracker:auto-resolve --days=3')
        ->expectsOutput('Eligible issues: 1')
        ->expectsOutput('Resolved issues: 1')
        ->assertExitCode(0);

    expect($issue->fresh()->status)->toBe('resolved')
        ->and($issue->fresh()->resolved_reason)->toBe('Automatically resolved after 3 days without new events.');
});

it('reopens a resolved issue when a new matching event is recorded', function () {
    $action = app(RecordThrowableAction::class);
    $first = recordAutoResolveRecurringThrowable($action);

    $resolved = app(IssueStatusService::class)->resolveAutomatically(
        $first->issue,
        'Automatically resolved after 14 days without new events.'
    );

    expect($resolved->status)->toBe('resolved')
        ->and($resolved->resolved_by_type)->toBe('auto');

    $second = recordAutoResolveRecurringThrowable($action);
    $issue = $resolved->fresh();

    expect($second?->issueWasReactivated)->toBeTrue()
        ->and($issue->status)->toBe('open')
        ->and($issue->resolved_at)->toBeNull()
        ->and($issue->resolved_by_type)->toBeNull()
        ->and($issue->resolved_reason)->toBeNull();
});

function createAutoResolveIssue(array $attributes = []): Issue
{
    return Issue::query()->create(array_merge([
        'fingerprint' => (string) Str::uuid(),
        'title' => 'Auto resolve issue',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Something failed',
        'first_seen_at' => now()->subDays(30),
        'last_seen_at' => now()->subDays(30),
        'total_events' => 1,
        'affected_users' => 0,
    ], $attributes));
}

function recordAutoResolveRecurringThrowable(RecordThrowableAction $action)
{
    return $action->handle(makeAutoResolveRecurringThrowable(), [
        'level' => 'error',
    ]);
}

function makeAutoResolveRecurringThrowable(): Throwable
{
    try {
        throwAutoResolveRecurringThrowable();
    } catch (Throwable $throwable) {
        return $throwable;
    }

    throw new RuntimeException('Unable to create test exception.');
}

function throwAutoResolveRecurringThrowable(): void
{
    throw new RuntimeException('Auto resolve recurring failure 12345');
}
