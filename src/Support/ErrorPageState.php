<?php

namespace Hewerthomn\ErrorTracker\Support;

use Hewerthomn\ErrorTracker\Data\RecordedEventResult;

class ErrorPageState
{
    protected ?RecordedEventResult $result = null;

    public function set(?RecordedEventResult $result): void
    {
        $this->result = $result;
    }

    public function get(): ?RecordedEventResult
    {
        return $this->result;
    }

    public function clear(): void
    {
        $this->result = null;
    }
}
