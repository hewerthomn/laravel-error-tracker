<?php

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Services\IssueStatusService;
use Illuminate\Support\Carbon;

it('can resolve reopen ignore mute and unmute an issue', function () {
    $issue = Issue::query()->create([
        'fingerprint' => sha1('issue-status-test'),
        'title' => 'Issue status test',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Something failed',
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'total_events' => 1,
        'affected_users' => 0,
    ]);

    $service = app(IssueStatusService::class);

    $resolved = $service->resolve($issue);

    expect($resolved->status)->toBe('resolved')
        ->and($resolved->resolved_at)->not->toBeNull()
        ->and($resolved->resolved_by_type)->toBe('manual')
        ->and($resolved->ignored_at)->toBeNull()
        ->and($resolved->muted_until)->toBeNull();

    $reopened = $service->reopen($resolved);

    expect($reopened->status)->toBe('open')
        ->and($reopened->resolved_at)->toBeNull()
        ->and($reopened->resolved_by_type)->toBeNull()
        ->and($reopened->resolved_reason)->toBeNull()
        ->and($reopened->ignored_at)->toBeNull();

    $ignored = $service->ignore($reopened);

    expect($ignored->status)->toBe('ignored')
        ->and($ignored->ignored_at)->not->toBeNull()
        ->and($ignored->resolved_at)->toBeNull();

    $muteUntil = Carbon::parse('2030-01-01 10:00:00');

    $muted = $service->mute($ignored, $muteUntil, 'Temporary noise');

    expect($muted->status)->toBe('muted')
        ->and($muted->muted_until?->format('Y-m-d H:i:s'))->toBe('2030-01-01 10:00:00')
        ->and($muted->mute_reason)->toBe('Temporary noise')
        ->and($muted->ignored_at)->toBeNull();

    $unmuted = $service->unmute($muted);

    expect($unmuted->status)->toBe('open')
        ->and($unmuted->muted_until)->toBeNull()
        ->and($unmuted->mute_reason)->toBeNull();
});

it('sets manual resolution metadata', function () {
    $issue = Issue::query()->create([
        'fingerprint' => sha1('manual-resolution-test'),
        'title' => 'Manual resolution test',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Something failed',
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'total_events' => 1,
        'affected_users' => 0,
    ]);

    $resolved = app(IssueStatusService::class)->resolveManually($issue, 'Fixed in deploy');

    expect($resolved->status)->toBe('resolved')
        ->and($resolved->resolved_at)->not->toBeNull()
        ->and($resolved->resolved_by_type)->toBe('manual')
        ->and($resolved->resolved_reason)->toBe('Fixed in deploy');
});

it('cleans resolution metadata when reopening', function () {
    $issue = Issue::query()->create([
        'fingerprint' => sha1('reopen-resolution-test'),
        'title' => 'Reopen resolution test',
        'level' => 'error',
        'status' => 'resolved',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Something failed',
        'first_seen_at' => now()->subDays(10),
        'last_seen_at' => now()->subDays(10),
        'total_events' => 1,
        'affected_users' => 0,
        'resolved_at' => now(),
        'resolved_by_type' => 'auto',
        'resolved_reason' => 'Automatically resolved after 14 days without new events.',
    ]);

    $reopened = app(IssueStatusService::class)->reopen($issue);

    expect($reopened->status)->toBe('open')
        ->and($reopened->resolved_at)->toBeNull()
        ->and($reopened->resolved_by_type)->toBeNull()
        ->and($reopened->resolved_reason)->toBeNull();
});
