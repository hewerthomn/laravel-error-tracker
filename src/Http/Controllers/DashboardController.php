<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $levels = $this->selectedLevels($request);
        $statuses = $this->selectedStatuses($request);

        $query = Issue::query()
            ->with([
                'lastEvent',
                'trends' => fn ($query) => $query
                    ->where('bucket_granularity', 'hour')
                    ->where('bucket_start', '>=', now()->subDay()->startOfHour())
                    ->orderBy('bucket_start'),
            ])
            ->orderByDesc('last_seen_at');

        $this->applyFilters($query, $request);

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
                'period' => $request->string('period')->toString() ?: '24h',
                'environment' => $request->string('environment')->toString(),
                'levels' => $levels,
                'statuses' => $statuses,
                'search' => $request->string('search')->toString(),
            ],
            'periodOptions' => [
                '1h' => 'Last 1 hour',
                '24h' => 'Last 24 hours',
                '7d' => 'Last 7 days',
                '30d' => 'Last 30 days',
            ],
            'levelOptions' => [
                'error' => 'Error',
                'warning' => 'Warning',
                'info' => 'Info',
                'debug' => 'Debug',
                'critical' => 'Critical',
            ],
            'statusOptions' => [
                'open' => 'Open',
                'resolved' => 'Resolved',
                'ignored' => 'Ignored',
                'muted' => 'Muted',
            ],
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

    protected function applyFilters($query, Request $request): void
    {
        if ($environment = $request->string('environment')->toString()) {
            $query->where('environment', $environment);
        }

        $levels = $this->selectedLevels($request);

        if ($levels !== []) {
            $query->whereIn('level', $levels);
        }

        $statuses = $this->selectedStatuses($request);

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('message_sample', 'like', "%{$search}%");
            });
        }

        [$from, $to] = $this->resolvePeriodRange($request->string('period')->toString() ?: '24h');

        if ($from && $to) {
            $query->whereBetween('last_seen_at', [$from, $to]);
        }
    }

    protected function resolvePeriodRange(string $period): array
    {
        $to = now();

        return match ($period) {
            '1h' => [$to->copy()->subHour(), $to],
            '24h' => [$to->copy()->subDay(), $to],
            '7d' => [$to->copy()->subDays(7), $to],
            '30d' => [$to->copy()->subDays(30), $to],
            default => [null, null],
        };
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
