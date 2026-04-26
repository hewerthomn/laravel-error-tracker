<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Contracts\ExceptionRecorder;
use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Hewerthomn\ErrorTracker\Services\IssueNotifier;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordThrowableAction
{
    public function __construct(
        protected ExceptionRecorder $recorder,
        protected IssueNotifier $issueNotifier,
    ) {}

    public function handle(Throwable $throwable, array $context = []): ?RecordedEventResult
    {
        if (! config('error-tracker.enabled', true)) {
            return null;
        }

        $allowedEnvironments = config('error-tracker.capture.environments', [app()->environment()]);

        if (! in_array(app()->environment(), $allowedEnvironments, true)) {
            return null;
        }

        if (! $this->passesSampling()) {
            return null;
        }

        try {
            $result = $this->recorder->record($throwable, $context);
        } catch (Throwable $trackerFailure) {
            Log::error('Error Tracker failed while recording throwable.', [
                'tracker_exception' => $trackerFailure::class,
                'tracker_message' => $trackerFailure->getMessage(),
                'original_exception' => $throwable::class,
                'original_message' => $throwable->getMessage(),
            ]);

            return null;
        }

        try {
            $this->issueNotifier->notifyWhenNeeded($result);
        } catch (Throwable $notificationFailure) {
            Log::warning('Error Tracker failed while sending notifications.', [
                'notification_exception' => $notificationFailure::class,
                'notification_message' => $notificationFailure->getMessage(),
                'issue_id' => $result->issue->id,
                'event_id' => $result->event->id,
            ]);
        }

        return $result;
    }

    protected function passesSampling(): bool
    {
        $rate = (float) config('error-tracker.capture.sample_rate', 1.0);

        if ($rate >= 1.0) {
            return true;
        }

        if ($rate <= 0.0) {
            return false;
        }

        return mt_rand() / mt_getrandmax() <= $rate;
    }
}
