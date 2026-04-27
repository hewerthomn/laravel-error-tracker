<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Support\Dashboard\QueryStringBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $levels = $this->selectedLevels($request);
        $statuses = $this->selectedStatuses($request);
        $period = $this->selectedPeriod($request);
        $sort = $this->selectedSort($request);
        $search = $this->selectedSearch($request);

        $query = Issue::query()
            ->with([
                'lastEvent',
                'trends' => fn ($query) => $query
                    ->where('bucket_granularity', 'hour')
                    ->where('bucket_start', '>=', now()->subDay()->startOfHour())
                    ->orderBy('bucket_start'),
            ]);

        $this->applyFilters($query, $request);
        $this->applySort($query, $sort);

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

        return view($view, [
            'appName' => config('app.name'),
            'issues' => $issues,
            'filters' => [
                'period' => $period,
                'environment' => $request->string('environment')->toString(),
                'levels' => $levels,
                'statuses' => $statuses,
                'search' => $search,
                'sort' => $sort,
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
            'quickLevelOptions' => [
                'all' => 'All',
                'warning' => 'Warning',
                'error' => 'Error',
                'critical' => 'Critical',
            ],
            'sortOptions' => [
                'recent' => 'Recent',
                'frequent' => 'Frequent',
                'oldest' => 'Oldest',
            ],
            'statusCounts' => $this->statusCounts($request),
            'levelCounts' => $this->levelCounts($request),
            'queryString' => QueryStringBuilder::fromRequest($request, route('error-tracker.index')),
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
     * @param  Builder<Issue>  $query
     */
    protected function applyFilters(
        Builder $query,
        Request $request,
        bool $includeStatus = true,
        bool $includeLevel = true,
    ): void {
        if ($environment = $request->string('environment')->toString()) {
            $query->where('environment', $environment);
        }

        $levels = $includeLevel ? $this->selectedLevels($request) : [];

        if ($levels !== []) {
            $query->whereIn('level', $levels);
        }

        $statuses = $includeStatus ? $this->selectedStatuses($request) : [];

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($search = $this->selectedSearch($request)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('message_sample', 'like', "%{$search}%");
            });
        }

        [$from, $to] = $this->resolvePeriodRange($this->selectedPeriod($request));

        if ($from && $to) {
            $query->whereBetween('last_seen_at', [$from, $to]);
        }
    }

    /**
     * @param  Builder<Issue>  $query
     */
    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'frequent' => $query->orderByDesc('total_events')->orderByDesc('last_seen_at'),
            'oldest' => $query->orderBy('first_seen_at')->orderBy('id'),
            default => $query->orderByDesc('last_seen_at')->orderByDesc('id'),
        };
    }

    /**
     * @return array{all: int, open: int, resolved: int, ignored: int, muted: int}
     */
    protected function statusCounts(Request $request): array
    {
        $counts = $this->countsFor($request, 'status', ['open', 'resolved', 'ignored', 'muted'], includeStatus: false);

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
    protected function levelCounts(Request $request): array
    {
        $counts = $this->countsFor($request, 'level', ['error', 'warning', 'critical', 'info', 'debug'], includeLevel: false);

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
        Request $request,
        string $column,
        array $keys,
        bool $includeStatus = true,
        bool $includeLevel = true,
    ): array {
        $query = Issue::query();

        $this->applyFilters($query, $request, $includeStatus, $includeLevel);

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

    protected function resolvePeriodRange(string $period): array
    {
        $to = now();

        return match ($period) {
            '1h' => [$to->copy()->subHour(), $to],
            '24h' => [$to->copy()->subDay(), $to],
            '7d' => [$to->copy()->subDays(7), $to],
            '30d' => [$to->copy()->subDays(30), $to],
            'all' => [null, null],
            default => [null, null],
        };
    }

    protected function selectedPeriod(Request $request): string
    {
        $period = $request->string('period')->toString() ?: 'all';

        return in_array($period, ['1h', '24h', '7d', '30d', 'all'], true)
            ? $period
            : 'all';
    }

    protected function selectedSort(Request $request): string
    {
        $sort = $request->string('sort')->toString() ?: 'recent';

        return in_array($sort, ['recent', 'frequent', 'oldest'], true)
            ? $sort
            : 'recent';
    }

    protected function selectedSearch(Request $request): string
    {
        $search = trim($request->string('q')->toString());

        if ($search !== '' || $request->query->has('q')) {
            return $search;
        }

        return trim($request->string('search')->toString());
    }

    protected function selectedStatuses(Request $request): array
    {
        $statuses = $request->input('status', []);

        if (is_string($statuses) && $statuses !== '') {
            $statuses = [$statuses];
        }

        if (! is_array($statuses)) {
            $statuses = [];
        }

        return collect($statuses)
            ->filter()
            ->map(fn ($status) => (string) $status)
            ->intersect(['open', 'resolved', 'ignored', 'muted'])
            ->values()
            ->all();
    }

    protected function selectedLevels(Request $request): array
    {
        $levels = $request->input('level', []);

        if (is_string($levels) && $levels !== '') {
            $levels = [$levels];
        }

        if (! is_array($levels)) {
            $levels = [];
        }

        return collect($levels)
            ->filter()
            ->map(fn ($level) => (string) $level)
            ->intersect(['error', 'warning', 'info', 'debug', 'critical'])
            ->values()
            ->all();
    }
}
