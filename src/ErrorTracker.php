<?php

namespace Hewerthomn\ErrorTracker;

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Throwable;

class ErrorTracker
{
    public function __construct(
        protected RecordThrowableAction $recordThrowableAction,
    ) {}

    public function capture(Throwable $throwable, array $context = []): ?RecordedEventResult
    {
        return $this->recordThrowableAction->handle($throwable, $context);
    }
}
