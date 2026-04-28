<?php

namespace Hewerthomn\ErrorTracker\Services;

use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Models\IssueNotification;
use Hewerthomn\ErrorTracker\Notifications\IssueTriggeredNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class IssueNotifier
{
    public function notifyWhenNeeded(RecordedEventResult $result): void
    {
        if (! config('error-tracker.notifications.enabled', true)) {
            return;
        }

        $trigger = $this->resolveTrigger($result);

        if (! $trigger) {
            return;
        }

        $routes = $this->resolveRoutes();
        $channels = array_keys($routes);

        if ($channels === []) {
            return;
        }

        try {
            if (! $this->passesCooldown($result->issue) || ! $this->passesHourlyLimit($result->issue)) {
                return;
            }
        } catch (Throwable $limiterFailure) {
            Log::warning('Error Tracker notification cooldown check failed.', [
                'exception' => $limiterFailure::class,
                'message' => $limiterFailure->getMessage(),
                'issue_id' => $result->issue->id,
                'trigger' => $trigger,
            ]);

            return;
        }

        Notification::routes($routes)->notify(
            new IssueTriggeredNotification(
                issue: $result->issue,
                trigger: $trigger,
                channels: $channels,
            )
        );

        try {
            $this->recordNotification($result->issue, $trigger);
        } catch (Throwable $recordFailure) {
            Log::warning('Error Tracker failed while recording notification metadata.', [
                'exception' => $recordFailure::class,
                'message' => $recordFailure->getMessage(),
                'issue_id' => $result->issue->id,
                'trigger' => $trigger,
            ]);
        }
    }

    protected function resolveTrigger(RecordedEventResult $result): ?string
    {
        if (
            $result->issueWasCreated &&
            config('error-tracker.notifications.notify_on_new_issue', true)
        ) {
            return 'new_issue';
        }

        if (
            $result->issueWasReactivated &&
            config('error-tracker.notifications.notify_on_reactivated', true)
        ) {
            return 'reactivated';
        }

        if (
            $result->issueWasRegression &&
            config('error-tracker.notifications.notify_on_regression', false)
        ) {
            return 'regression';
        }

        return null;
    }

    protected function resolveRoutes(): array
    {
        $configuredChannels = config('error-tracker.notifications.channels', ['mail']);
        $routes = [];

        if (in_array('mail', $configuredChannels, true)) {
            $mailTo = config('error-tracker.notifications.mail_to');

            if (is_string($mailTo) && trim($mailTo) !== '') {
                $routes['mail'] = trim($mailTo);
            }
        }

        if (in_array('slack', $configuredChannels, true)) {
            $slackChannel = config('error-tracker.notifications.slack_channel');

            if (is_string($slackChannel) && trim($slackChannel) !== '') {
                $routes['slack'] = trim($slackChannel);
            }
        }

        return $routes;
    }

    protected function passesCooldown(Issue $issue): bool
    {
        $cooldownMinutes = $this->positiveIntegerConfig('error-tracker.notifications.cooldown_minutes');

        if ($cooldownMinutes === null) {
            return true;
        }

        $latestNotification = IssueNotification::query()
            ->where('issue_id', $issue->id)
            ->latest('sent_at')
            ->first();

        if (! $latestNotification?->sent_at) {
            return true;
        }

        return $latestNotification->sent_at->lte(now()->subMinutes($cooldownMinutes));
    }

    protected function passesHourlyLimit(Issue $issue): bool
    {
        $maxPerHour = $this->positiveIntegerConfig('error-tracker.notifications.max_per_issue_per_hour');

        if ($maxPerHour === null) {
            return true;
        }

        $sentInLastHour = IssueNotification::query()
            ->where('issue_id', $issue->id)
            ->where('sent_at', '>=', now()->subHour())
            ->count();

        return $sentInLastHour < $maxPerHour;
    }

    protected function recordNotification(Issue $issue, string $reason): void
    {
        IssueNotification::query()->create([
            'issue_id' => $issue->id,
            'channel' => null,
            'reason' => $reason,
            'sent_at' => Carbon::now(),
        ]);
    }

    protected function positiveIntegerConfig(string $key): ?int
    {
        $value = config($key);

        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
