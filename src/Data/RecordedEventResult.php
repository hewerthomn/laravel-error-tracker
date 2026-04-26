<?php

namespace Hewerthomn\ErrorTracker\Data;

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Issue;

class RecordedEventResult
{
    public function __construct(
        public Issue $issue,
        public Event $event,
        public bool $issueWasCreated = false,
        public bool $issueWasReactivated = false,
    ) {}
}
