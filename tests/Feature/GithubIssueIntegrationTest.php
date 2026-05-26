<?php

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\ExternalIssue;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    config([
        'error-tracker.integrations.issue_trackers.github.enabled' => true,
        'error-tracker.integrations.issue_trackers.github.token' => 'ghp_secret-token',
        'error-tracker.integrations.issue_trackers.github.repository' => 'acme/app',
        'error-tracker.integrations.issue_trackers.github.labels' => ['bug', 'error-tracker'],
        'error-tracker.integrations.issue_trackers.github.assignees' => [],
    ]);
});

it('creates a github issue and persists the external issue link', function () {
    $issue = githubIntegrationIssue();

    Http::fake([
        'api.github.com/repos/acme/app/issues' => Http::response(githubIssuePayload(123), 201),
    ]);

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.github.store', $issue))
        ->assertRedirect(route('error-tracker.issues.show', $issue))
        ->assertSessionHas('status', 'GitHub issue #123 linked.');

    expect(ExternalIssue::query()->count())->toBe(1);

    $externalIssue = ExternalIssue::query()->firstOrFail();

    expect($externalIssue->provider)->toBe('github')
        ->and($externalIssue->repository)->toBe('acme/app')
        ->and($externalIssue->external_number)->toBe(123)
        ->and($externalIssue->created_from_event_id)->toBe($issue->last_event_id);

    Http::assertSent(function ($request): bool {
        $payload = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://api.github.com/repos/acme/app/issues'
            && $payload['title'] === '[production] RuntimeException in app/Http/Controllers/HomeController.php:42'
            && str_contains($payload['body'], '## Error Tracker issue')
            && str_contains($payload['body'], 'Application stack frames')
            && ! str_contains($payload['body'], 'ghp_secret-token');
    });
});

it('does not create github issues when the integration is disabled', function () {
    $issue = githubIntegrationIssue();
    config(['error-tracker.integrations.issue_trackers.github.enabled' => false]);
    Http::fake();

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.github.store', $issue))
        ->assertRedirect(route('error-tracker.issues.show', $issue))
        ->assertSessionHas('status', 'GitHub issue integration is disabled.');

    Http::assertNothingSent();
    expect(ExternalIssue::query()->count())->toBe(0);
});

it('does not link existing github issues when the integration is disabled', function () {
    $issue = githubIntegrationIssue();
    config(['error-tracker.integrations.issue_trackers.github.enabled' => false]);
    Http::fake();

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.link', $issue), [
        'external_issue' => 'https://github.com/acme/app/issues/123',
    ])
        ->assertRedirect(route('error-tracker.issues.show', $issue))
        ->assertSessionHas('status', 'GitHub issue integration is disabled.');

    Http::assertNothingSent();
    expect(ExternalIssue::query()->count())->toBe(0);
});

it('does not call github when the token is missing', function () {
    $issue = githubIntegrationIssue();
    config(['error-tracker.integrations.issue_trackers.github.token' => '']);
    Http::fake();

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.github.store', $issue))
        ->assertSessionHas('status', 'GitHub token is not configured.');

    Http::assertNothingSent();
});

it('requires a valid configured github repository', function () {
    $issue = githubIntegrationIssue();
    config(['error-tracker.integrations.issue_trackers.github.repository' => 'invalid']);
    Http::fake();

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.github.store', $issue))
        ->assertSessionHas('status', 'GitHub repository must be configured as owner/repository.');

    Http::assertNothingSent();
});

it('handles github 401 while creating an issue', function () {
    /** @var TestCase $this */
    githubCreateFailsWith($this, 401, 'GitHub token is invalid or expired.');
});

it('handles github 403 while creating an issue', function () {
    /** @var TestCase $this */
    githubCreateFailsWith($this, 403, 'GitHub token does not have permission to access this repository.');
});

it('handles github 404 while creating an issue', function () {
    /** @var TestCase $this */
    githubCreateFailsWith($this, 404, 'GitHub repository or issue was not found, or the token cannot access it.');
});

it('handles github 410 while creating an issue', function () {
    /** @var TestCase $this */
    githubCreateFailsWith($this, 410, 'GitHub issues are disabled or unavailable for this repository.');
});

it('handles github 422 while creating an issue', function () {
    /** @var TestCase $this */
    githubCreateFailsWith($this, 422, 'GitHub rejected the issue payload. Check labels, assignees, and repository settings.');
});

it('links an existing github issue by url', function () {
    $issue = githubIntegrationIssue();
    Http::fake([
        'api.github.com/repos/acme/app/issues/123' => Http::response(githubIssuePayload(123), 200),
    ]);

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.link', $issue), [
        'external_issue' => 'https://github.com/acme/app/issues/123',
    ])->assertSessionHas('status', 'GitHub issue #123 linked.');

    expect($issue->externalIssues()->first()?->external_number)->toBe(123);
});

it('links an existing github issue by owner repository number syntax', function () {
    $issue = githubIntegrationIssue();
    Http::fake([
        'api.github.com/repos/acme/app/issues/124' => Http::response(githubIssuePayload(124), 200),
    ]);

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.link', $issue), [
        'external_issue' => 'acme/app#124',
    ])->assertSessionHas('status', 'GitHub issue #124 linked.');

    expect($issue->externalIssues()->first()?->external_number)->toBe(124);
});

it('links an existing github issue by hash number with the default repository', function () {
    $issue = githubIntegrationIssue();
    Http::fake([
        'api.github.com/repos/acme/app/issues/125' => Http::response(githubIssuePayload(125), 200),
    ]);

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.link', $issue), [
        'external_issue' => '#125',
    ])->assertSessionHas('status', 'GitHub issue #125 linked.');

    expect($issue->externalIssues()->first()?->repository)->toBe('acme/app');
});

it('links an existing github issue by bare number with the default repository', function () {
    $issue = githubIntegrationIssue();
    Http::fake([
        'api.github.com/repos/acme/app/issues/126' => Http::response(githubIssuePayload(126), 200),
    ]);

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.link', $issue), [
        'external_issue' => '126',
    ])->assertSessionHas('status', 'GitHub issue #126 linked.');

    expect($issue->externalIssues()->first()?->external_number)->toBe(126);
});

it('refreshes github issue status manually', function () {
    $issue = githubIntegrationIssue();
    $externalIssue = githubExternalIssue($issue, 123, ['state' => 'open']);

    Http::fake([
        'api.github.com/repos/acme/app/issues/123' => Http::response(githubIssuePayload(123, ['state' => 'closed', 'title' => 'Fixed issue']), 200),
    ]);

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.sync', [$issue, $externalIssue]))
        ->assertSessionHas('status', 'External issue refreshed from GitHub.');

    $externalIssue->refresh();

    expect($externalIssue->state)->toBe('closed')
        ->and($externalIssue->title)->toBe('Fixed issue')
        ->and($externalIssue->last_synced_at)->not->toBeNull();
});

it('does not refresh github issue status when the integration is disabled', function () {
    $issue = githubIntegrationIssue();
    $externalIssue = githubExternalIssue($issue, 123, ['state' => 'open']);
    config(['error-tracker.integrations.issue_trackers.github.enabled' => false]);
    Http::fake();

    /** @var TestCase $this */
    $this->post(route('error-tracker.issues.external-issues.sync', [$issue, $externalIssue]))
        ->assertRedirect(route('error-tracker.issues.show', $issue))
        ->assertSessionHas('status', 'GitHub issue integration is disabled.');

    Http::assertNothingSent();
    expect($externalIssue->refresh()->state)->toBe('open');
});

it('unlinks without modifying the github issue', function () {
    $issue = githubIntegrationIssue();
    $externalIssue = githubExternalIssue($issue, 123);
    Http::fake();

    /** @var TestCase $this */
    $this->delete(route('error-tracker.issues.external-issues.destroy', [$issue, $externalIssue]))
        ->assertSessionHas('status', 'GitHub issue unlinked successfully. The GitHub issue was not modified.');

    Http::assertNothingSent();
    expect(ExternalIssue::query()->count())->toBe(0);
});

it('renders the issue detail external issue card when unlinked', function () {
    $issue = githubIntegrationIssue();

    /** @var TestCase $this */
    $this->get(route('error-tracker.issues.show', $issue))
        ->assertOk()
        ->assertSee('External issue')
        ->assertSee('GitHub not linked')
        ->assertSee('Create GitHub issue')
        ->assertSee('Link existing issue')
        ->assertSee('external-issue-link-row', false)
        ->assertSee('Link issue')
        ->assertSee('Accepted: GitHub issue URL, owner/repository#123, #123, or 123.');
});

it('does not render the issue detail external issue card when github integration is disabled', function () {
    $issue = githubIntegrationIssue();
    githubExternalIssue($issue, 123);
    config(['error-tracker.integrations.issue_trackers.github.enabled' => false]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.issues.show', $issue))
        ->assertOk()
        ->assertDontSee('External issue')
        ->assertDontSee('GitHub not linked')
        ->assertDontSee('Create GitHub issue')
        ->assertDontSee('Link existing issue')
        ->assertDontSee('GitHub #123')
        ->assertDontSee('Open on GitHub')
        ->assertDontSee('Refresh from GitHub')
        ->assertDontSee('Unlink');
});

it('renders the issue detail external issue card when linked', function () {
    $issue = githubIntegrationIssue();
    githubExternalIssue($issue, 123, [
        'state' => 'closed',
        'title' => '[production] RuntimeException in routes/web.php:16',
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.issues.show', $issue))
        ->assertOk()
        ->assertSeeInOrder(['GitHub #123', 'closed', 'Open on GitHub'])
        ->assertSee('GitHub #123')
        ->assertSee('badge badge-muted', false)
        ->assertSee('Open on GitHub')
        ->assertSee('Refresh from GitHub')
        ->assertDontSee('Sync status')
        ->assertSee('Unlink')
        ->assertSee('Title')
        ->assertSee('Last synced')
        ->assertSee('[production] RuntimeException in routes/web.php:16')
        ->assertDontSee('<span class="field-label">Repository</span>', false)
        ->assertSee('Unlink this GitHub issue? This will only remove the local association. The GitHub issue will not be changed.', false);
});

it('renders a compact github badge on the issue list', function () {
    $issue = githubIntegrationIssue();
    githubExternalIssue($issue, 123);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all']))
        ->assertOk()
        ->assertSee('GH #123')
        ->assertSee('https://github.com/acme/app/issues/123');
});

it('does not render the compact github badge on the issue list when github integration is disabled', function () {
    $issue = githubIntegrationIssue();
    githubExternalIssue($issue, 123);
    config(['error-tracker.integrations.issue_trackers.github.enabled' => false]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all']))
        ->assertOk()
        ->assertSee($issue->title)
        ->assertDontSee('GH #123')
        ->assertDontSee('https://github.com/acme/app/issues/123');
});

it('does not break dashboard pages when the external issues table is missing', function () {
    $issue = githubIntegrationIssue();
    Schema::dropIfExists('error_tracker_external_issues');

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all']))
        ->assertOk()
        ->assertSee($issue->title);

    $this->get(route('error-tracker.issues.show', $issue))
        ->assertOk()
        ->assertSee('GitHub not linked');
});

it('masks the github token in configuration diagnostics', function () {
    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('GitHub Issue Integration')
        ->assertSee('Token')
        ->assertSee('configured')
        ->assertDontSee('ghp_secret-token');
});

it('includes github integration and external issues table in doctor checks', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:doctor', ['--verbose' => true])
        ->expectsOutputToContain('OK error_tracker_external_issues table')
        ->expectsOutputToContain('INFO GitHub issue integration')
        ->expectsOutputToContain('OK GitHub repository')
        ->expectsOutputToContain('OK GitHub token')
        ->assertSuccessful();
});

it('does not use old sync status wording in visible docs or views', function () {
    $paths = [
        __DIR__.'/../../README.md',
        __DIR__.'/../../CHANGELOG.md',
        __DIR__.'/../../docs/github-issue-integration.md',
        __DIR__.'/../../resources/views/dashboard/issue-show.blade.php',
    ];

    foreach ($paths as $path) {
        expect(file_get_contents($path))->not->toContain('Sync status');
    }
});

function githubIntegrationIssue(): Issue
{
    $issue = Issue::query()->create([
        'fingerprint' => 'github-integration-'.Str::uuid(),
        'title' => 'RuntimeException in HomeController',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'production',
        'exception_class' => 'RuntimeException',
        'message_sample' => 'Something failed safely.',
        'first_seen_at' => now()->subHour(),
        'last_seen_at' => now(),
        'total_events' => 3,
        'affected_users' => 1,
    ]);

    $event = Event::query()->create([
        'issue_id' => $issue->id,
        'uuid' => (string) Str::uuid(),
        'occurred_at' => now(),
        'level' => 'error',
        'exception_class' => 'RuntimeException',
        'message' => 'Something failed safely.',
        'file' => app_path('Http/Controllers/HomeController.php'),
        'line' => 42,
        'request_method' => 'GET',
        'request_path' => '/home',
        'route_name' => 'home',
        'environment' => 'production',
        'trace_json' => [
            [
                'file' => app_path('Http/Controllers/HomeController.php'),
                'line' => 42,
                'class' => 'App\\Http\\Controllers\\HomeController',
                'type' => '->',
                'function' => 'index',
            ],
        ],
        'context_json' => [
            'safe' => 'value',
            'token' => 'sensitive',
        ],
    ]);

    $issue->forceFill(['last_event_id' => $event->id])->save();

    return $issue->refresh();
}

/**
 * @param  array<string, mixed>  $overrides
 */
function githubExternalIssue(Issue $issue, int $number, array $overrides = []): ExternalIssue
{
    return ExternalIssue::query()->create(array_merge([
        'issue_id' => $issue->id,
        'provider' => 'github',
        'repository' => 'acme/app',
        'external_id' => (string) (1000 + $number),
        'external_number' => $number,
        'title' => 'GitHub issue '.$number,
        'url' => 'https://github.com/acme/app/issues/'.$number,
        'state' => 'open',
        'metadata_json' => ['number' => $number],
        'last_synced_at' => now(),
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function githubIssuePayload(int $number, array $overrides = []): array
{
    return array_merge([
        'id' => 1000 + $number,
        'node_id' => 'I_kwDO'.$number,
        'number' => $number,
        'title' => 'GitHub issue '.$number,
        'html_url' => 'https://github.com/acme/app/issues/'.$number,
        'state' => 'open',
        'state_reason' => null,
        'updated_at' => now()->toIso8601String(),
    ], $overrides);
}

function githubCreateFailsWith(TestCase $test, int $status, string $message): void
{
    $issue = githubIntegrationIssue();

    Http::fake([
        'api.github.com/repos/acme/app/issues' => Http::response(['message' => 'GitHub error'], $status),
    ]);

    $test->post(route('error-tracker.issues.external-issues.github.store', $issue))
        ->assertRedirect(route('error-tracker.issues.show', $issue))
        ->assertSessionHas('status', $message);

    expect(ExternalIssue::query()->count())->toBe(0);
}
