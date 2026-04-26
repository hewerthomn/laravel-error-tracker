<?php

namespace Hewerthomn\ErrorTracker\Contracts;

use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Throwable;

interface ExceptionRecorder
{
    public function record(Throwable $throwable, array $context = []): RecordedEventResult;
}
