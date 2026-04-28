<?php

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Support\Dashboard\IssueSearchParser;
use Hewerthomn\ErrorTracker\Support\Dashboard\IssueSearchQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('filters by status', function () {
    createIssueSearchIssue(['title' => 'Open issue', 'status' => 'open']);
    createIssueSearchIssue(['title' => 'Resolved issue', 'status' => 'resolved']);

    expect(issueSearchTitles(['status' => 'resolved']))->toBe(['Resolved issue']);
});

it('filters by multiple statuses', function () {
    createIssueSearchIssue(['title' => 'Open issue', 'status' => 'open']);
    createIssueSearchIssue(['title' => 'Muted issue', 'status' => 'muted']);
    createIssueSearchIssue(['title' => 'Ignored issue', 'status' => 'ignored']);

    expect(issueSearchTitles(['status' => ['open', 'muted']], sort: 'title', direction: 'asc'))
        ->toBe(['Muted issue', 'Open issue']);
});

it('filters by level', function () {
    createIssueSearchIssue(['title' => 'Error issue', 'level' => 'error']);
    createIssueSearchIssue(['title' => 'Warning issue', 'level' => 'warning']);

    expect(issueSearchTitles(['level' => 'warning']))->toBe(['Warning issue']);
});

it('filters by environment', function () {
    createIssueSearchIssue(['title' => 'Production issue', 'environment' => 'production']);
    createIssueSearchIssue(['title' => 'Staging issue', 'environment' => 'staging']);

    expect(issueSearchTitles(['environment' => 'production']))->toBe(['Production issue']);
});

it('filters by resolved by type', function () {
    createIssueSearchIssue(['title' => 'Auto issue', 'status' => 'resolved', 'resolved_by_type' => 'auto']);
    createIssueSearchIssue(['title' => 'Manual issue', 'status' => 'resolved', 'resolved_by_type' => 'manual']);

    expect(issueSearchTitles(['resolved_by_type' => 'auto']))->toBe(['Auto issue']);
});

it('filters by period', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-28 12:00:00'));

    createIssueSearchIssue(['title' => 'Fresh issue', 'last_seen_at' => now()->subHours(3)]);
    createIssueSearchIssue(['title' => 'Old issue', 'last_seen_at' => now()->subDays(2)]);

    expect(issueSearchTitles(['period' => '24h']))->toBe(['Fresh issue']);
});

it('filters by exception class', function () {
    createIssueSearchIssue(['title' => 'Query issue', 'exception_class' => 'Illuminate\\Database\\QueryException']);
    createIssueSearchIssue(['title' => 'Runtime issue', 'exception_class' => RuntimeException::class]);

    expect(issueSearchTitles([], 'class:QueryException'))->toBe(['Query issue']);
});

it('filters by free text in issue fields', function () {
    createIssueSearchIssue(['title' => 'Checkout failed', 'message_sample' => 'Payment timeout']);
    createIssueSearchIssue(['title' => 'Profile failed', 'message_sample' => 'Avatar timeout']);

    expect(issueSearchTitles([], 'checkout'))->toBe(['Checkout failed']);
});

it('filters by route name through events', function () {
    $matching = createIssueSearchIssue(['title' => 'Route issue']);
    createIssueSearchEvent($matching, ['route_name' => 'users.store']);

    $other = createIssueSearchIssue(['title' => 'Other issue']);
    createIssueSearchEvent($other, ['route_name' => 'orders.store']);

    expect(issueSearchTitles([], 'route:users.store'))->toBe(['Route issue']);
});

it('filters by file through events', function () {
    $matching = createIssueSearchIssue(['title' => 'File issue']);
    createIssueSearchEvent($matching, ['file' => 'app/Http/Controllers/UserController.php']);

    $other = createIssueSearchIssue(['title' => 'Other issue']);
    createIssueSearchEvent($other, ['file' => 'app/Http/Controllers/OrderController.php']);

    expect(issueSearchTitles([], 'file:UserController.php'))->toBe(['File issue']);
});

it('filters by user through events', function () {
    $matching = createIssueSearchIssue(['title' => 'User issue']);
    createIssueSearchEvent($matching, ['user_id' => '123', 'user_label' => 'Ada Lovelace']);

    $other = createIssueSearchIssue(['title' => 'Other issue']);
    createIssueSearchEvent($other, ['user_id' => '999', 'user_label' => 'Grace Hopper']);

    expect(issueSearchTitles([], 'user:Ada'))->toBe(['User issue'])
        ->and(issueSearchTitles([], 'user:123'))->toBe(['User issue']);
});

it('filters issues that have feedback', function () {
    $matching = createIssueSearchIssue(['title' => 'Feedback issue']);
    $event = createIssueSearchEvent($matching);
    $event->feedback()->create([
        'feedback_token' => (string) Str::uuid(),
        'message' => 'I saw this.',
    ]);

    createIssueSearchIssue(['title' => 'No feedback issue']);

    expect(issueSearchTitles([], 'has:feedback'))->toBe(['Feedback issue']);
});

it('orders by total events', function () {
    createIssueSearchIssue(['title' => 'Small issue', 'total_events' => 1]);
    createIssueSearchIssue(['title' => 'Large issue', 'total_events' => 20]);

    expect(issueSearchTitles(['sort' => 'total_events', 'direction' => 'desc']))->toBe(['Large issue', 'Small issue']);
});

it('keeps last seen descending as default order', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-28 12:00:00'));

    createIssueSearchIssue(['title' => 'Older issue', 'last_seen_at' => now()->subHours(2)]);
    createIssueSearchIssue(['title' => 'Newer issue', 'last_seen_at' => now()->subMinutes(5)]);

    expect(issueSearchTitles())->toBe(['Newer issue', 'Older issue']);
});

function issueSearchTitles(array $filters = [], string $query = '', ?string $sort = null, ?string $direction = null): array
{
    if ($sort !== null) {
        $filters['sort'] = $sort;
    }

    if ($direction !== null) {
        $filters['direction'] = $direction;
    }

    $search = (new IssueSearchParser)->parse($query, $filters);
    $builder = Issue::query();
    $queryBuilder = new IssueSearchQuery;

    $queryBuilder->apply($builder, $search);
    $queryBuilder->sort($builder, $search);

    return $builder->pluck('title')->all();
}

function createIssueSearchIssue(array $attributes = []): Issue
{
    return Issue::query()->create(array_merge([
        'fingerprint' => (string) Str::uuid(),
        'title' => 'Search issue',
        'level' => 'error',
        'status' => 'open',
        'environment' => 'testing',
        'exception_class' => RuntimeException::class,
        'message_sample' => 'Search failure',
        'first_seen_at' => now()->subDay(),
        'last_seen_at' => now(),
        'total_events' => 1,
        'affected_users' => 0,
    ], $attributes));
}

function createIssueSearchEvent(Issue $issue, array $attributes = [])
{
    return $issue->events()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'occurred_at' => now(),
        'level' => $issue->level,
        'exception_class' => $issue->exception_class,
        'message' => $issue->message_sample,
        'file' => $issue->exception_class,
        'line' => 1,
        'environment' => $issue->environment,
        'feedback_token' => (string) Str::uuid(),
    ], $attributes));
}
