@extends('error-tracker::layout', ['title' => config('error-tracker.dashboard.title_prefix').' - '.$appName])

@section('content')
    @php
        $badgeClassForLevel = function (?string $level): string {
            return match (strtolower((string) $level)) {
                'error', 'critical', 'alert', 'emergency' => 'badge-error',
                'warning' => 'badge-warning',
                'info', 'notice' => 'badge-info',
                'debug' => 'badge-neutral',
                default => 'badge-neutral',
            };
        };

        $badgeClassForStatus = function (?string $status): string {
            return match (strtolower((string) $status)) {
                'open' => 'badge-info',
                'resolved' => 'badge-success',
                'ignored' => 'badge-warning',
                'muted' => 'badge-muted',
                default => 'badge-neutral',
            };
        };

        $badgeClassForEnvironment = function (?string $environment): string {
            return match (strtolower((string) $environment)) {
                'production' => 'badge-error',
                'staging' => 'badge-warning',
                'local' => 'badge-info',
                default => 'badge-neutral',
            };
        };
    @endphp

    @include('error-tracker::partials.page-header', [
        'title' => $appName,
        'subtitle' => null,
        'breadcrumbs' => [],
        'badges' => [],
    ])

    <div class="card filters-card">
        <form method="GET" action="{{ route('error-tracker.index') }}">
            <div class="filters-grid-primary">
                <div class="filter-field">
                    <label for="period" class="filters-label">Period</label>
                    <select id="period" name="period">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['period'] ?? '24h') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field">
                    <label for="level" class="filters-label">Level</label>
                    @php
                        $selectedLevels = $filters['levels'] ?? [];
                        $selectedLevelLabels = collect($levelOptions)
                            ->only($selectedLevels)
                            ->values();
                    @endphp

                    <details class="multi-select" id="level">
                        <summary class="multi-select-summary">
                            <span>
                                {{ $selectedLevelLabels->isNotEmpty() ? $selectedLevelLabels->join(', ') : 'All levels' }}
                            </span>
                        </summary>

                        <div class="multi-select-panel">
                            @foreach ($levelOptions as $value => $label)
                                <label class="multi-select-option">
                                    <input
                                        type="checkbox"
                                        name="level[]"
                                        value="{{ $value }}"
                                        @checked(in_array($value, $selectedLevels, true))
                                    >
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                </div>

                <div class="filter-field">
                    <label for="status" class="filters-label">Status</label>
                    @php
                        $selectedStatuses = $filters['statuses'] ?? [];
                        $selectedStatusLabels = collect($statusOptions)
                            ->only($selectedStatuses)
                            ->values();
                    @endphp

                    <details class="multi-select" id="status">
                        <summary class="multi-select-summary">
                            <span>
                                {{ $selectedStatusLabels->isNotEmpty() ? $selectedStatusLabels->join(', ') : 'All statuses' }}
                            </span>
                        </summary>

                        <div class="multi-select-panel">
                            @foreach ($statusOptions as $value => $label)
                                <label class="multi-select-option">
                                    <input
                                        type="checkbox"
                                        name="status[]"
                                        value="{{ $value }}"
                                        @checked(in_array($value, $selectedStatuses, true))
                                    >
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                </div>
            </div>

            <div class="filters-grid-secondary">
                <div class="filter-field filter-field-search">
                    <label for="search" class="filters-label">Search</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        placeholder="Search by issue title, exception class, or message..."
                        value="{{ $filters['search'] ?? '' }}"
                    >
                </div>

                <div class="filter-field">
                    @if ($canFilterEnvironment)
                        <label for="environment" class="filters-label">Environment</label>
                        <select id="environment" name="environment">
                            <option value="" @selected(($filters['environment'] ?? '') === '')>
                                All environments
                            </option>

                            @foreach ($environmentOptions as $environment)
                                <option value="{{ $environment }}" @selected(($filters['environment'] ?? '') === $environment)>
                                    {{ ucfirst($environment) }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <label for="environment-disabled" class="filters-label">Environment</label>
                        <input
                            id="environment-disabled"
                            type="text"
                            value="{{ $environmentFallbackLabel }}"
                            class="filter-control-disabled"
                            disabled
                        >
                    @endif
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>

                <div class="filter-actions">
                    <a href="{{ route('error-tracker.index') }}" class="btn btn-outline">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Issues</h2>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Issue</th>
                    <th>Signals</th>
                    <th>Trend</th>
                    <th>Total</th>
                    <th>Last seen</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
                    @php
                        $trendBuckets = $issue->trends->keyBy(
                            fn ($trend) => $trend->bucket_start?->format('Y-m-d H:00')
                        );
                        $trendStart = now()->subDay()->startOfHour();
                        $trendValues = collect(range(0, 23))->map(function (int $offset) use ($trendBuckets, $trendStart): int {
                            $bucketKey = $trendStart->copy()->addHours($offset)->format('Y-m-d H:00');

                            return (int) ($trendBuckets->get($bucketKey)?->events_count ?? 0);
                        });
                        $trendMax = $trendValues->max();
                    @endphp

                    <tr>
                        <td>
                            <a href="{{ route('error-tracker.issues.show', $issue) }}" class="issue-title-link">
                                {{ $issue->title }}
                            </a>

                            <div class="issue-meta-line">
                                {{ $issue->exception_class ?: 'Unknown exception' }}
                            </div>
                        </td>

                        <td>
                            <div class="table-badges">
                                <span class="badge {{ $badgeClassForLevel($issue->level) }}">{{ $issue->level }}</span>
                                <span class="badge {{ $badgeClassForStatus($issue->status) }}">{{ $issue->status }}</span>

                                @if ($showEnvironmentBadge)
                                    <span class="badge {{ $badgeClassForEnvironment($issue->environment) }}">
                                        {{ $issue->environment }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td>
                            @if ($trendMax > 0)
                                <div class="mini-trend" aria-label="Events trend over the last 24 hours">
                                    @foreach ($trendValues as $trendValue)
                                        <span
                                            class="mini-trend-bar"
                                            style="height: {{ max(3, (int) round(($trendValue / $trendMax) * 24)) }}px;"
                                            title="{{ $trendValue }} {{ \Illuminate\Support\Str::plural('event', $trendValue) }}"
                                        ></span>
                                    @endforeach
                                </div>
                            @else
                                <div class="mini-trend-empty" title="No trend data for the last 24 hours"></div>
                            @endif
                        </td>

                        <td>{{ number_format($issue->total_events) }}</td>

                        <td>
                            <div>{{ optional($issue->last_seen_at)?->diffForHumans() ?: '—' }}</div>
                            <div class="issue-meta-line">
                                {{ optional($issue->last_seen_at)?->format('d/m/Y H:i:s') ?: '—' }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">No issues found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 18px;">
            {{ $issues->links() }}
        </div>
    </div>
@endsection
