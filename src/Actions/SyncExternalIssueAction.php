<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Exceptions\ExternalIssueTrackerException;
use Hewerthomn\ErrorTracker\Integrations\IssueTrackers\GithubIssueTrackerProvider;
use Hewerthomn\ErrorTracker\Models\ExternalIssue;
use Illuminate\Support\Carbon;

class SyncExternalIssueAction
{
    public function __construct(
        protected GithubIssueTrackerProvider $github,
    ) {}

    public function execute(ExternalIssue $externalIssue): ExternalIssue
    {
        if ($externalIssue->provider !== 'github') {
            throw ExternalIssueTrackerException::configuration('Only GitHub external issues can be synced in this release.');
        }

        if (! (bool) config('error-tracker.integrations.issue_trackers.github.enabled', false)) {
            throw ExternalIssueTrackerException::configuration('GitHub issue integration is disabled.');
        }

        if (trim((string) config('error-tracker.integrations.issue_trackers.github.token', '')) === '') {
            throw ExternalIssueTrackerException::configuration('GitHub token is not configured.');
        }

        $result = $this->github->getIssue($externalIssue->repository, $externalIssue->external_number);

        $externalIssue->forceFill([
            'external_id' => $result->externalId,
            'title' => $result->title,
            'url' => $result->url,
            'state' => $result->state,
            'metadata_json' => $result->metadata,
            'last_synced_at' => Carbon::now(),
        ])->save();

        return $externalIssue->refresh();
    }
}
