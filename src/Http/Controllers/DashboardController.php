<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Support\Dashboard\IssueSearchParser;
use Hewerthomn\ErrorTracker\Support\Dashboard\IssueSearchQuery;
use Hewerthomn\ErrorTracker\Support\Dashboard\QueryStringBuilder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(Request $request, IssueSearchParser $searchParser, IssueSearchQuery $issueSearchQuery)
    {
        $queryText = $request->query->has('q')
            ? $request->string('q')->toString()
            : $request->string('search')->toString();
        $search = $searchParser->parse($queryText, $request->query());

        $query = Issue::query()
            ->with([
                'lastEvent',
                'trends' => fn ($query) => $query
                    ->where('bucket_granularity', 'hour')
                    ->where('bucket_start', '>=', now()->subDay()->startOfHour())
                    ->orderBy('bucket_start'),
            ]);

        $issueSearchQuery->apply($query, $search);
        $issueSearchQuery->sort($query, $search);

        $issues = $query->paginate(20)->withQueryString();

        $environmentOptions = Issue::query()
            ->select('environment')
            ->whereNotNull('environment')
            ->distinct()
            ->orderBy('environment')
            ->pluck('environment')
            ->values();

        $hasMultipleEnvironments = $environmentOptions->count() > 1;

        /** @var view-string $view */
        $view = 'error-tracker::dashboard.index';
        $queryString = QueryStringBuilder::fromRequest($request, route('error-tracker.index'));

        return view($view, [
            'appName' => config('app.name'),
            'issues' => $issues,
            'search' => $search,
            'filters' => [
                'period' => $search['period'],
                'from' => $search['from'],
                'to' => $search['to'],
                'environment' => $search['environments'][0] ?? '',
                'environments' => $search['environments'],
                'levels' => $search['levels'],
                'statuses' => $search['statuses'],
                'q' => $queryText,
                'search' => $queryText,
                'resolved_by_type' => $search['resolved_by_type'],
                'has_feedback' => $search['has_feedback'],
                'sort' => $search['sort'],
                'direction' => $search['direction'],
            ],
            'periodOptions' => [
                '1h' => '1h',
                '24h' => '24h',
                '7d' => '7d',
                '30d' => '30d',
                'all' => 'All',
            ],
            'levelOptions' => [
                'error' => 'Error',
                'warning' => 'Warning',
                'critical' => 'Critical',
                'info' => 'Info',
                'debug' => 'Debug',
            ],
            'statusOptions' => [
                'open' => 'Open',
                'resolved' => 'Resolved',
                'ignored' => 'Ignored',
                'muted' => 'Muted',
            ],
            'resolvedByTypeOptions' => [
                'auto' => 'Auto',
                'manual' => 'Manual',
            ],
            'hasFeedbackOptions' => [
                '1' => 'Has feedback',
            ],
            'sortOptions' => [
                'last_seen_at' => 'Last seen',
                'first_seen_at' => 'First seen',
                'total_events' => 'Total events',
                'affected_users' => 'Affected users',
                'level' => 'Level',
            ],
            'directionOptions' => [
                'desc' => 'Desc',
                'asc' => 'Asc',
            ],
            'statusCounts' => $this->statusCounts($search, $issueSearchQuery),
            'levelCounts' => $this->levelCounts($search, $issueSearchQuery),
            'activeFilterChips' => $this->activeFilterChips($search, $queryText),
            'clearFiltersUrl' => route('error-tracker.index'),
            'queryString' => $queryString,
            'environmentOptions' => $environmentOptions,
            'environmentFallbackLabel' => $environmentOptions->count() === 1
                ? ucfirst((string) $environmentOptions->first())
                : 'Current environment',
            'canFilterEnvironment' => $hasMultipleEnvironments && $this->shouldShowEnvironmentFilter($hasMultipleEnvironments),
            'showEnvironmentBadge' => $this->shouldShowEnvironmentBadge($hasMultipleEnvironments),
        ]);
    }

    protected function shouldShowEnvironmentFilter(bool $hasMultipleEnvironments): bool
    {
        $value = config('error-tracker.dashboard.show_environment_filter', 'auto');

        if ($value === 'auto') {
            return $hasMultipleEnvironments;
        }

        return (bool) $value;
    }

    protected function shouldShowEnvironmentBadge(bool $hasMultipleEnvironments): bool
    {
        $value = config('error-tracker.dashboard.show_environment_badge', 'auto');

        if ($value === 'auto') {
            return $hasMultipleEnvironments;
        }

        return (bool) $value;
    }

    /**
     * @return array{all: int, open: int, resolved: int, ignored: int, muted: int}
     */
    protected function statusCounts(array $search, IssueSearchQuery $issueSearchQuery): array
    {
        $counts = $this->countsFor($search, $issueSearchQuery, 'status', ['open', 'resolved', 'ignored', 'muted']);

        return [
            'all' => $counts['all'] ?? 0,
            'open' => $counts['open'] ?? 0,
            'resolved' => $counts['resolved'] ?? 0,
            'ignored' => $counts['ignored'] ?? 0,
            'muted' => $counts['muted'] ?? 0,
        ];
    }

    /**
     * @return array{all: int, error: int, warning: int, critical: int, info: int, debug: int}
     */
    protected function levelCounts(array $search, IssueSearchQuery $issueSearchQuery): array
    {
        $counts = $this->countsFor($search, $issueSearchQuery, 'level', ['error', 'warning', 'critical', 'info', 'debug']);

        return [
            'all' => $counts['all'] ?? 0,
            'error' => $counts['error'] ?? 0,
            'warning' => $counts['warning'] ?? 0,
            'critical' => $counts['critical'] ?? 0,
            'info' => $counts['info'] ?? 0,
            'debug' => $counts['debug'] ?? 0,
        ];
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, int>
     */
    protected function countsFor(
        array $search,
        IssueSearchQuery $issueSearchQuery,
        string $column,
        array $keys,
    ): array {
        $query = Issue::query();
        $searchForCounts = $search;
        $searchForCounts[$column === 'status' ? 'statuses' : 'levels'] = [];

        $issueSearchQuery->apply($query, $searchForCounts);

        $counts = $query
            ->selectRaw($column.', count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(fn ($count): int => (int) $count);

        $result = ['all' => (int) $counts->sum()];

        foreach ($keys as $key) {
            $result[$key] = (int) $counts->get($key, 0);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $search
     * @return array<int, array{label: string, value: string}>
     */
    protected function activeFilterChips(array $search, string $queryText): array
    {
        $chips = [];

        if (trim($queryText) !== '') {
            $chips[] = ['label' => 'Search', 'value' => $queryText];
        }

        foreach ($search['statuses'] as $status) {
            $chips[] = ['label' => 'Status', 'value' => ucfirst($status)];
        }

        foreach ($search['levels'] as $level) {
            $chips[] = ['label' => 'Level', 'value' => ucfirst($level)];
        }

        foreach ($search['environments'] as $environment) {
            $chips[] = ['label' => 'Environment', 'value' => ucfirst($environment)];
        }

        foreach ([
            'exception_class' => 'Exception',
            'message' => 'Message',
            'fingerprint' => 'Fingerprint',
            'route' => 'Route',
            'path' => 'Path',
            'url' => 'URL',
            'file' => 'File',
            'user' => 'User',
            'status_code' => 'Status code',
            'resolved_by_type' => 'Resolved',
        ] as $key => $label) {
            if (($search[$key] ?? null) !== null && $search[$key] !== '') {
                $chips[] = ['label' => $label, 'value' => (string) $search[$key]];
            }
        }

        if (($search['has_feedback'] ?? null) === true) {
            $chips[] = ['label' => 'Feedback', 'value' => 'Has feedback'];
        } elseif (($search['has_feedback'] ?? null) === false) {
            $chips[] = ['label' => 'Feedback', 'value' => 'No feedback'];
        }

        if (($search['period'] ?? 'all') !== 'all') {
            $chips[] = [
                'label' => 'Period',
                'value' => $search['period'] === 'custom'
                    ? trim(($search['from'] ?? '').' - '.($search['to'] ?? ''))
                    : (string) $search['period'],
            ];
        }

        return $chips;
    }
}
