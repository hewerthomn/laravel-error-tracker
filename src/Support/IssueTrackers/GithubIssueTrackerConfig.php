<?php

namespace Hewerthomn\ErrorTracker\Support\IssueTrackers;

class GithubIssueTrackerConfig
{
    public function enabled(): bool
    {
        return (bool) config('error-tracker.integrations.issue_trackers.github.enabled', false);
    }
}
