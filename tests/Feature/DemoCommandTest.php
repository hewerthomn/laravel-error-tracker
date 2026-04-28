<?php

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Feedback;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Models\IssueNotification;
use Hewerthomn\ErrorTracker\Models\IssueTrend;
use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('blocks demo generation in production without force', function () {
    app()->detectEnvironment(fn (): string => 'production');

    /** @var TestCase $this */
    $this->artisan('error-tracker:demo')
        ->expectsOutputToContain('Refusing to create demo data in production without --force')
        ->assertExitCode(Command::FAILURE);

    expect(Issue::query()->where('fingerprint', 'like', 'demo:%')->count())->toBe(0);
});

it('creates demo issues events and trends', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:demo', [
        '--count' => 24,
        '--with-resolved' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(Issue::query()->where('fingerprint', 'like', 'demo:%')->count())->toBeGreaterThanOrEqual(8)
        ->and(Event::query()->whereHas('issue', fn ($query) => $query->where('fingerprint', 'like', 'demo:%'))->count())->toBeGreaterThanOrEqual(24)
        ->and(IssueTrend::query()->whereHas('issue', fn ($query) => $query->where('fingerprint', 'like', 'demo:%'))->count())->toBeGreaterThan(0);

    $event = Event::query()
        ->whereHas('issue', fn ($query) => $query->where('fingerprint', 'like', 'demo:%'))
        ->first();

    $traceJson = json_encode($event->trace_json, JSON_UNESCAPED_SLASHES);

    expect($event)->not->toBeNull()
        ->and($event->context_json['_demo'] ?? false)->toBeTrue()
        ->and($traceJson)->toContain('app/Http/Controllers/CheckoutController.php')
        ->and($traceJson)->toContain('vendor/laravel/framework')
        ->and($traceJson)->toContain('vendor/spatie');
});

it('creates demo feedback when requested', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:demo', [
        '--with-feedback' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(Feedback::query()->count())->toBeGreaterThan(0);
});

it('creates demo notification history when requested and the table exists', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:demo', [
        '--with-notifications' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(IssueNotification::query()->count())->toBeGreaterThan(0);
});

it('purges only demo data', function () {
    $real = Issue::query()->create([
        'fingerprint' => 'real:checkout',
        'title' => 'Real issue',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Real data',
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'total_events' => 1,
        'affected_users' => 1,
    ]);

    $real->events()->create([
        'uuid' => (string) Str::uuid(),
        'occurred_at' => now(),
        'level' => 'error',
        'exception_class' => RuntimeException::class,
        'message' => 'Real data',
        'environment' => 'testing',
        'feedback_token' => (string) Str::uuid(),
    ]);

    /** @var TestCase $this */
    $this->artisan('error-tracker:demo', [
        '--with-feedback' => true,
        '--with-notifications' => true,
    ])->assertExitCode(Command::SUCCESS);

    $this->artisan('error-tracker:demo', [
        '--purge' => true,
    ])->assertExitCode(Command::SUCCESS);

    expect(Issue::query()->where('fingerprint', 'like', 'demo:%')->count())->toBe(0)
        ->and(Event::query()->whereHas('issue', fn ($query) => $query->where('fingerprint', 'like', 'demo:%'))->count())->toBe(0)
        ->and(Issue::query()->whereKey($real->id)->exists())->toBeTrue()
        ->and(Event::query()->where('issue_id', $real->id)->exists())->toBeTrue();
});

it('fresh removes and recreates demo data', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:demo', ['--count' => 16])
        ->assertExitCode(Command::SUCCESS);

    $firstEventCount = Event::query()
        ->whereHas('issue', fn ($query) => $query->where('fingerprint', 'like', 'demo:%'))
        ->count();

    $this->artisan('error-tracker:demo', [
        '--fresh' => true,
        '--count' => 12,
    ])->assertExitCode(Command::SUCCESS);

    $secondEventCount = Event::query()
        ->whereHas('issue', fn ($query) => $query->where('fingerprint', 'like', 'demo:%'))
        ->count();

    expect($firstEventCount)->toBeGreaterThan(0)
        ->and($secondEventCount)->toBeGreaterThan(0)
        ->and(Issue::query()->where('fingerprint', 'like', 'demo:%')->count())->toBeGreaterThanOrEqual(8);
});

it('skips optional notification history when the table is missing', function () {
    Schema::dropIfExists('error_tracker_issue_notifications');

    /** @var TestCase $this */
    $this->artisan('error-tracker:demo', [
        '--with-notifications' => true,
    ])
        ->expectsOutputToContain('Skipping notifications')
        ->assertExitCode(Command::SUCCESS);
});
