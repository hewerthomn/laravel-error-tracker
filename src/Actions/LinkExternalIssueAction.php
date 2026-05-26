<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Data\ExternalIssueResult;
use Hewerthomn\ErrorTracker\Exceptions\ExternalIssueTrackerException;
use Hewerthomn\ErrorTracker\Integrations\IssueTrackers\GithubIssueTrackerProvider;
use Hewerthomn\ErrorTracker\Models\ExternalIssue;
use Hewerthomn\ErrorTracker\Models\Issue;
use Illuminate\Support\Carbon;

class LinkExternalIssueAction
{
    public function __construct(
        protected GithubIssueTrackerProvider $github,
    ) {}

    public function execute(Issue $issue, string $input): ExternalIssue
    {
        [$repository, $number] = $this->parse($input);
        $this->ensureConfigured();

        $result = $this->github->getIssue($repository, $number);

        return $this->persist($issue, $result);
    }

    /**
     * @return array{0: string, 1: int}
     */
    protected function parse(string $input): array
    {
        $input = trim($input);

        if (preg_match('#^https://github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)/issues/([1-9][0-9]*)/?$#', $input, $matches)) {
            return [$matches[1].'/'.$matches[2], (int) $matches[3]];
        }

        if (preg_match('/^([A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+)#([1-9][0-9]*)$/', $input, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        if (preg_match('/^#?([1-9][0-9]*)$/', $input, $matches)) {
            return [$this->defaultRepository(), (int) $matches[1]];
        }

        throw ExternalIssueTrackerException::configuration('Enter a valid GitHub issue URL, owner/repository#number, #number, or number.');
    }

    protected function ensureConfigured(): void
    {
        if (! (bool) config('error-tracker.integrations.issue_trackers.github.enabled', false)) {
            throw ExternalIssueTrackerException::configuration('GitHub issue integration is disabled.');
        }

        if (trim((string) config('error-tracker.integrations.issue_trackers.github.token', '')) === '') {
            throw ExternalIssueTrackerException::configuration('GitHub token is not configured.');
        }
    }

    protected function defaultRepository(): string
    {
        $repository = trim((string) config('error-tracker.integrations.issue_trackers.github.repository', ''));

        if (! preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository)) {
            throw ExternalIssueTrackerException::configuration('GitHub repository must be configured as owner/repository to use a bare issue number.');
        }

        return $repository;
    }

    protected function persist(Issue $issue, ExternalIssueResult $result): ExternalIssue
    {
        if (! ExternalIssue::tableExists()) {
            throw ExternalIssueTrackerException::configuration('External issues table is missing. Publish the package migrations and run migrate.');
        }

        return ExternalIssue::query()->updateOrCreate(
            [
                'issue_id' => $issue->id,
                'provider' => $result->provider,
                'repository' => $result->repository,
                'external_number' => $result->externalNumber,
            ],
            [
                'external_id' => $result->externalId,
                'title' => $result->title,
                'url' => $result->url,
                'state' => $result->state,
                'created_from_event_id' => null,
                'metadata_json' => $result->metadata,
                'last_synced_at' => Carbon::now(),
            ],
        );
    }
}
