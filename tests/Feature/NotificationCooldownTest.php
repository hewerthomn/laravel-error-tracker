<?php

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Contracts\ExceptionRecorder;
use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Models\IssueNotification;
use Hewerthomn\ErrorTracker\Services\IssueNotifier;
use Hewerthomn\ErrorTracker\Services\IssueStatusService;
use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-04-28 12:00:00'));

    config([
        'error-tracker.notifications.enabled' => true,
        'error-tracker.notifications.channels' => ['mail'],
        'error-tracker.notifications.mail_to' => 'alerts@example.test',
        'error-tracker.notifications.notify_on_new_issue' => true,
        'error-tracker.notifications.notify_on_regression' => true,
        'error-tracker.notifications.notify_on_reactivated' => true,
        'error-tracker.notifications.cooldown_minutes' => 30,
        'error-tracker.notifications.max_per_issue_per_hour' => 3,
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('notifies a new issue when there is no cooldown', function () {
    Notification::fake();

    config([
        'error-tracker.notifications.cooldown_minutes' => 0,
        'error-tracker.notifications.max_per_issue_per_hour' => 0,
    ]);

    $result = recordNotificationCooldownThrowable();

    expect($result)->not->toBeNull()
        ->and(IssueNotification::query()->count())->toBe(1)
        ->and(IssueNotification::query()->first()?->reason)->toBe('new_issue');
});

it('does not notify the same issue inside the cooldown', function () {
    Notification::fake();

    recordNotificationCooldownThrowable();
    recordNotificationCooldownThrowable();

    expect(IssueNotification::query()->count())->toBe(1)
        ->and(IssueNotification::query()->first()?->reason)->toBe('new_issue');
});

it('notifies again after the cooldown has passed', function () {
    Notification::fake();

    recordNotificationCooldownThrowable();

    Carbon::setTestNow(now()->addMinutes(31));

    recordNotificationCooldownThrowable();

    expect(IssueNotification::query()->count())->toBe(2)
        ->and(IssueNotification::query()->latest('sent_at')->first()?->reason)->toBe('regression');
});

it('respects max notifications per issue per hour', function () {
    Notification::fake();

    config([
        'error-tracker.notifications.cooldown_minutes' => 0,
        'error-tracker.notifications.max_per_issue_per_hour' => 2,
    ]);

    recordNotificationCooldownThrowable();
    recordNotificationCooldownThrowable();
    recordNotificationCooldownThrowable();

    expect(IssueNotification::query()->count())->toBe(2);
});

it('notifies a reactivated issue when allowed', function () {
    Notification::fake();

    $first = recordNotificationCooldownThrowable();

    app(IssueStatusService::class)->resolveAutomatically(
        $first->issue,
        'Automatically resolved after 14 days without new events.'
    );

    Carbon::setTestNow(now()->addMinutes(31));

    $second = recordNotificationCooldownThrowable();

    expect($second?->issueWasReactivated)->toBeTrue()
        ->and(IssueNotification::query()->count())->toBe(2)
        ->and(IssueNotification::query()->latest('sent_at')->first()?->reason)->toBe('reactivated');
});

it('does not break capture when notification sending fails', function () {
    $action = new RecordThrowableAction(
        app(ExceptionRecorder::class),
        new class extends IssueNotifier
        {
            public function notifyWhenNeeded(RecordedEventResult $result): void
            {
                throw new RuntimeException('Mail transport failed.');
            }
        }
    );

    $result = $action->handle(makeNotificationCooldownThrowable(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull()
        ->and(IssueNotification::query()->count())->toBe(0);
});

it('updates notification history after notification is sent', function () {
    Notification::fake();

    $result = recordNotificationCooldownThrowable();
    $notification = IssueNotification::query()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->issue_id)->toBe($result->issue->id)
        ->and($notification->sent_at?->format('Y-m-d H:i:s'))->toBe('2026-04-28 12:00:00');
});

it('does not throw when notification history table is missing and capture continues', function () {
    Notification::fake();
    Schema::dropIfExists('error_tracker_issue_notifications');

    $result = recordNotificationCooldownThrowable();

    expect($result)->not->toBeNull()
        ->and(Issue::query()->count())->toBe(1)
        ->and(Event::query()->count())->toBe(1);

    Notification::assertNothingSent();
});

it('shows notification metadata on the issue detail page', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);
    Notification::fake();

    $result = recordNotificationCooldownThrowable();

    /** @var TestCase $this */
    $this->get(route('error-tracker.issues.show', $result->issue))
        ->assertOk()
        ->assertSee('Last notified at')
        ->assertSee('Notification count')
        ->assertSee('Recent notifications')
        ->assertSee('new issue');
});

function recordNotificationCooldownThrowable()
{
    return app(RecordThrowableAction::class)->handle(makeNotificationCooldownThrowable(), [
        'level' => 'error',
    ]);
}

function makeNotificationCooldownThrowable(): Throwable
{
    try {
        throwNotificationCooldownThrowable();
    } catch (Throwable $throwable) {
        return $throwable;
    }

    throw new RuntimeException('Unable to create notification cooldown throwable.');
}

function throwNotificationCooldownThrowable(): void
{
    throw new RuntimeException('Notification cooldown recurring failure');
}
