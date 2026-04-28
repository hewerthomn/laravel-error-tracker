<?php

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('filters issues by status from the query string', function () {
    createDashboardIssue([
        'title' => 'Open dashboard issue',
        'status' => 'open',
    ]);

    createDashboardIssue([
        'title' => 'Resolved dashboard issue',
        'status' => 'resolved',
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', [
        'period' => 'all',
        'status' => 'resolved',
    ]))
        ->assertOk()
        ->assertSee('Resolved dashboard issue')
        ->assertDontSee('Open dashboard issue');
});

it('filters issues by level from the query string', function () {
    createDashboardIssue([
        'title' => 'Warning dashboard issue',
        'level' => 'warning',
    ]);

    createDashboardIssue([
        'title' => 'Error dashboard issue',
        'level' => 'error',
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', [
        'period' => 'all',
        'level' => 'warning',
    ]))
        ->assertOk()
        ->assertSee('Warning dashboard issue')
        ->assertDontSee('Error dashboard issue');
});

it('filters issues by period from the query string', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-27 12:00:00'));

    createDashboardIssue([
        'title' => 'Fresh period issue',
        'last_seen_at' => now()->subHours(2),
    ]);

    createDashboardIssue([
        'title' => 'Old period issue',
        'last_seen_at' => now()->subDays(2),
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => '24h']))
        ->assertOk()
        ->assertSee('Fresh period issue')
        ->assertDontSee('Old period issue');
});

it('sorts issues by recent frequent and oldest', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-27 12:00:00'));

    createDashboardIssue([
        'title' => 'Recent issue',
        'first_seen_at' => now()->subDays(2),
        'last_seen_at' => now()->subMinutes(5),
        'total_events' => 3,
    ]);

    createDashboardIssue([
        'title' => 'Frequent issue',
        'first_seen_at' => now()->subDay(),
        'last_seen_at' => now()->subHour(),
        'total_events' => 20,
    ]);

    createDashboardIssue([
        'title' => 'Oldest issue',
        'first_seen_at' => now()->subDays(10),
        'last_seen_at' => now()->subMinutes(30),
        'total_events' => 1,
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all', 'sort' => 'recent']))
        ->assertOk()
        ->assertSeeInOrder(['Recent issue', 'Oldest issue', 'Frequent issue']);

    $this->get(route('error-tracker.index', ['period' => 'all', 'sort' => 'frequent']))
        ->assertOk()
        ->assertSeeInOrder(['Frequent issue', 'Recent issue', 'Oldest issue']);

    $this->get(route('error-tracker.index', ['period' => 'all', 'sort' => 'oldest']))
        ->assertOk()
        ->assertSeeInOrder(['Oldest issue', 'Recent issue', 'Frequent issue']);
});

it('calculates status counters for the current non-status filters', function () {
    createDashboardIssue([
        'title' => 'Open counted issue',
        'level' => 'error',
        'status' => 'open',
    ]);

    createDashboardIssue([
        'title' => 'Resolved counted issue',
        'level' => 'error',
        'status' => 'resolved',
    ]);

    createDashboardIssue([
        'title' => 'Muted warning issue',
        'level' => 'warning',
        'status' => 'muted',
    ]);

    /** @var TestCase $this */
    $response = $this->get(route('error-tracker.index', [
        'period' => 'all',
        'level' => 'error',
    ]));

    $response->assertOk();

    expect($response->viewData('statusCounts'))->toMatchArray([
        'all' => 2,
        'open' => 1,
        'resolved' => 1,
        'ignored' => 0,
        'muted' => 0,
    ]);
});

it('renders the issue sidebar search input and sort segmented control', function () {
    createDashboardIssue([
        'title' => 'Rendered dashboard issue',
        'status' => 'open',
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all']))
        ->assertOk()
        ->assertSee('Error Tracker')
        ->assertSee('href="'.route('error-tracker.index').'"', false)
        ->assertSee('Status')
        ->assertSee('Search or use operators')
        ->assertSee('Resolved by')
        ->assertSee('Feedback')
        ->assertSee('Last seen')
        ->assertSee('First seen')
        ->assertSee('Total events')
        ->assertSee('aria-label="Filter issue period"', false)
        ->assertDontSee('<h2 class="filter-sidebar-heading">Period</h2>', false)
        ->assertSee('Rendered dashboard issue');
});

it('renders active filter chips and a clean clear filters link', function () {
    createDashboardIssue([
        'title' => 'Filtered dashboard issue',
        'status' => 'open',
        'level' => 'error',
        'environment' => 'production',
    ]);

    createDashboardIssue([
        'title' => 'Hidden dashboard issue',
        'status' => 'resolved',
        'level' => 'warning',
        'environment' => 'staging',
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', [
        'period' => 'all',
        'q' => 'dashboard status:open',
        'level' => 'error',
        'environment' => 'production',
        'resolved_by_type' => 'auto',
        'has_feedback' => '1',
    ]))
        ->assertOk()
        ->assertSee('active-filter-chip', false)
        ->assertSee('Search')
        ->assertSee('dashboard status:open')
        ->assertSee('Status')
        ->assertSee('Open')
        ->assertSee('Level')
        ->assertSee('Error')
        ->assertSee('Environment')
        ->assertSee('Production')
        ->assertSee('Feedback')
        ->assertSee('Has feedback')
        ->assertSee('href="'.route('error-tracker.index').'"', false)
        ->assertDontSee('Hidden dashboard issue');
});

it('uses get query parameters for dashboard filters', function () {
    createDashboardIssue(['title' => 'URL dashboard issue', 'environment' => 'testing']);
    createDashboardIssue(['title' => 'Production dashboard issue', 'environment' => 'production']);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all']))
        ->assertOk()
        ->assertSee('method="GET"', false)
        ->assertSee('name="q"', false)
        ->assertSee('status=resolved', false)
        ->assertSee('level=warning', false)
        ->assertSee('environment=production', false);
});

function createDashboardIssue(array $attributes = []): Issue
{
    return Issue::query()->create(array_merge([
        'fingerprint' => (string) Str::uuid(),
        'title' => 'Dashboard issue',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Dashboard failure',
        'first_seen_at' => now()->subDay(),
        'last_seen_at' => now(),
        'total_events' => 1,
        'affected_users' => 0,
    ], $attributes));
}
