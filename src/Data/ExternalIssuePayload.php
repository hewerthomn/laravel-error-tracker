<?php

namespace Hewerthomn\ErrorTracker\Data;

class ExternalIssuePayload
{
    /**
     * @param  array<int, string>  $labels
     * @param  array<int, string>  $assignees
     */
    public function __construct(
        public readonly string $repository,
        public readonly string $title,
        public readonly string $body,
        public readonly array $labels = [],
        public readonly array $assignees = [],
    ) {}
}
