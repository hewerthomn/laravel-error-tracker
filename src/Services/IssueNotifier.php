<?php

namespace Hewerthomn\ErrorTracker\Services;

use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Hewerthomn\ErrorTracker\Notifications\IssueTriggeredNotification;
use Illuminate\Support\Facades\Notification;

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

        Notification::routes($routes)->notify(
            new IssueTriggeredNotification(
                issue: $result->issue,
                trigger: $trigger,
                channels: $channels,
            )
        );
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
            return 'reactivated_issue';
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
}
