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
            <div class="filters-grid">
                <div>
                    <label for="search" class="filters-label">Search</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        placeholder="Search by issue title, exception class, or message..."
                        value="{{ $filters['search'] ?? '' }}"
                    >
                </div>

                <div>
                    <label for="level" class="filters-label">Level</label>
                    <select id="level" name="level">
                        @foreach ($levelOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['level'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="period" class="filters-label">Period</label>
                    <select id="period" name="period">
                        @foreach ($periodOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['period'] ?? '24h') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="filters-grid-second">
                @if ($showEnvironmentFilter)
                    <div>
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
                    </div>
                @endif

                <div>
                    <label for="status" class="filters-label">Status</label>
                    <select id="status" name="status">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>

                <div class="filters-actions">
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
                    <th>Total</th>
                    <th>Last seen</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
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

                        <td>{{ number_format($issue->total_events) }}</td>

                        <td>
                            <div>{{ optional($issue->last_seen_at)?->diffForHumans() ?: '—' }}</div>
                            <div class="issue-meta-line">
                                {{ optional($issue->last_seen_at)?->format('d/m/Y H:i:s') ?: '—' }}
                            </div>
                        </td>

                        <td>
                            <a href="{{ route('error-tracker.issues.show', $issue) }}" class="btn btn-outline">
                                View
                            </a>
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