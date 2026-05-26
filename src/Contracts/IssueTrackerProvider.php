<?php

namespace Hewerthomn\ErrorTracker\Contracts;

use Hewerthomn\ErrorTracker\Data\ExternalIssuePayload;
use Hewerthomn\ErrorTracker\Data\ExternalIssueResult;

interface IssueTrackerProvider
{
    public function createIssue(ExternalIssuePayload $payload): ExternalIssueResult;

    public function getIssue(string $repository, string|int $number): ExternalIssueResult;

    public function provider(): string;
}
