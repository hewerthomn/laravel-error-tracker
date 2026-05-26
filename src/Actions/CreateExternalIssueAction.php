<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Data\ExternalIssuePayload;
use Hewerthomn\ErrorTracker\Data\ExternalIssueResult;
use Hewerthomn\ErrorTracker\Exceptions\ExternalIssueTrackerException;
use Hewerthomn\ErrorTracker\Integrations\IssueTrackers\GithubIssueTrackerProvider;
use Hewerthomn\ErrorTracker\Models\ExternalIssue;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CreateExternalIssueAction
{
    public function __construct(
        protected GithubIssueTrackerProvider $github,
        protected BuildExternalIssueBodyAction $bodyBuilder,
        protected PathNormalizer $pathNormalizer,
    ) {}

    public function execute(Issue $issue): ExternalIssue
    {
        $repository = $this->configuredRepository();
        $issue->loadMissing('lastEvent');

        $result = $this->github->createIssue(new ExternalIssuePayload(
            repository: $repository,
            title: $this->title($issue),
            body: $this->bodyBuilder->execute($issue),
            labels: $this->listConfig('labels'),
            assignees: $this->listConfig('assignees'),
        ));

        return $this->persist($issue, $result, $issue->last_event_id);
    }

    protected function configuredRepository(): string
    {
        if (! (bool) config('error-tracker.integrations.issue_trackers.github.enabled', false)) {
            throw ExternalIssueTrackerException::configuration('GitHub issue integration is disabled.');
        }

        if (trim((string) config('error-tracker.integrations.issue_trackers.github.token', '')) === '') {
            throw ExternalIssueTrackerException::configuration('GitHub token is not configured.');
        }

        $repository = trim((string) config('error-tracker.integrations.issue_trackers.github.repository', ''));

        if (! $this->validRepository($repository)) {
            throw ExternalIssueTrackerException::configuration('GitHub repository must be configured as owner/repository.');
        }

        return $repository;
    }

    protected function title(Issue $issue): string
    {
        $event = $issue->lastEvent;
        $location = $event?->file
            ? ($this->pathNormalizer->normalize($event->file).($event->line ? ':'.$event->line : ''))
            : 'unknown location';

        $replacements = [
            '{environment}' => $issue->environment ?: 'unknown',
            '{exception_class}' => $issue->exception_class ?: $event?->exception_class ?: 'Unknown exception',
            '{location}' => $location,
            '{level}' => $issue->level ?: 'error',
        ];

        $template = (string) config('error-tracker.integrations.issue_trackers.github.title_template', '[{environment}] {exception_class} in {location}');
        $title = strtr($template, $replacements);

        return Str::limit(trim(preg_replace('/[[:cntrl:]]+/', ' ', $title) ?: $title), 250, '...');
    }

    /**
     * @return array<int, string>
     */
    protected function listConfig(string $key): array
    {
        $value = config('error-tracker.integrations.issue_trackers.github.'.$key, []);

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): string => trim($item))
            ->values()
            ->all();
    }

    protected function persist(Issue $issue, ExternalIssueResult $result, ?int $eventId): ExternalIssue
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
                'created_from_event_id' => $eventId,
                'metadata_json' => $result->metadata,
                'last_synced_at' => Carbon::now(),
            ],
        );
    }

    protected function validRepository(string $repository): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repository);
    }
}
