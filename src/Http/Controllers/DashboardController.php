<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Issue::query()
            ->with('lastEvent')
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

        return view('error-tracker::dashboard.index', [
            'appName' => config('app.name'),
            'issues' => $issues,
            'filters' => [
                'period' => $request->string('period')->toString() ?: '24h',
                'environment' => $request->string('environment')->toString(),
                'level' => $request->string('level')->toString(),
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'periodOptions' => [
                '1h' => 'Last 1 hour',
                '24h' => 'Last 24 hours',
                '7d' => 'Last 7 days',
                '30d' => 'Last 30 days',
            ],
            'levelOptions' => [
                '' => 'All levels',
                'error' => 'Error',
                'warning' => 'Warning',
                'info' => 'Info',
                'debug' => 'Debug',
                'critical' => 'Critical',
            ],
            'statusOptions' => [
                '' => 'All statuses',
                'open' => 'Open',
                'resolved' => 'Resolved',
                'ignored' => 'Ignored',
                'muted' => 'Muted',
            ],
            'environmentOptions' => $environmentOptions,
            'showEnvironmentFilter' => $this->shouldShowEnvironmentFilter($hasMultipleEnvironments),
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

        if ($level = $request->string('level')->toString()) {
            $query->where('level', $level);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
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
}
