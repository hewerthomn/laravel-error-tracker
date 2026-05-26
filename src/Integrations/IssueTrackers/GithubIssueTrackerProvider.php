<?php

namespace Hewerthomn\ErrorTracker\Integrations\IssueTrackers;

use Hewerthomn\ErrorTracker\Contracts\IssueTrackerProvider;
use Hewerthomn\ErrorTracker\Data\ExternalIssuePayload;
use Hewerthomn\ErrorTracker\Data\ExternalIssueResult;
use Hewerthomn\ErrorTracker\Exceptions\ExternalIssueTrackerException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class GithubIssueTrackerProvider implements IssueTrackerProvider
{
    public function createIssue(ExternalIssuePayload $payload): ExternalIssueResult
    {
        $data = [
            'title' => $payload->title,
            'body' => $payload->body,
        ];

        if ($payload->labels !== []) {
            $data['labels'] = $payload->labels;
        }

        if ($payload->assignees !== []) {
            $data['assignees'] = $payload->assignees;
        }

        try {
            $response = $this->client()
                ->post($this->endpoint($payload->repository, 'issues'), $data);
        } catch (Throwable) {
            throw new ExternalIssueTrackerException('GitHub issue integration failed. Try again later.');
        }

        $this->ensureSuccessful($response);

        return $this->resultFromResponse($payload->repository, $response);
    }

    public function getIssue(string $repository, string|int $number): ExternalIssueResult
    {
        try {
            $response = $this->client()
                ->get($this->endpoint($repository, 'issues/'.(int) $number));
        } catch (Throwable) {
            throw new ExternalIssueTrackerException('GitHub issue integration failed. Try again later.');
        }

        $this->ensureSuccessful($response);

        return $this->resultFromResponse($repository, $response);
    }

    public function provider(): string
    {
        return 'github';
    }

    protected function client(): PendingRequest
    {
        $token = trim((string) config('error-tracker.integrations.issue_trackers.github.token', ''));

        return Http::baseUrl('https://api.github.com')
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->withToken($token);
    }

    protected function endpoint(string $repository, string $path): string
    {
        return '/repos/'.$repository.'/'.$path;
    }

    protected function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw ExternalIssueTrackerException::githubStatus($response->status());
    }

    protected function resultFromResponse(string $repository, Response $response): ExternalIssueResult
    {
        $data = $response->json();
        $data = is_array($data) ? $data : [];

        return new ExternalIssueResult(
            provider: $this->provider(),
            repository: $repository,
            externalId: isset($data['id']) ? (string) $data['id'] : null,
            externalNumber: (int) ($data['number'] ?? 0),
            title: (string) ($data['title'] ?? 'GitHub issue'),
            url: (string) ($data['html_url'] ?? ''),
            state: isset($data['state']) ? (string) $data['state'] : null,
            metadata: $this->metadata($data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function metadata(array $data): array
    {
        return array_filter([
            'id' => $data['id'] ?? null,
            'node_id' => $data['node_id'] ?? null,
            'number' => $data['number'] ?? null,
            'state' => $data['state'] ?? null,
            'state_reason' => $data['state_reason'] ?? null,
            'html_url' => $data['html_url'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
