@extends('error-tracker::layout', ['title' => config('error-tracker.dashboard.title_prefix').' - '.config('app.name')])

@section('content')
    @php
        $levelBadgeClass = match (strtolower((string) $issue->level)) {
            'error', 'critical', 'alert', 'emergency' => 'badge-error',
            'warning' => 'badge-warning',
            'info', 'notice' => 'badge-info',
            'debug' => 'badge-neutral',
            default => 'badge-neutral',
        };

        $statusBadgeClass = match (strtolower((string) $issue->status)) {
            'open' => 'badge-info',
            'resolved' => 'badge-success',
            'ignored' => 'badge-warning',
            'muted' => 'badge-muted',
            default => 'badge-neutral',
        };

        $environmentBadgeClass = match (strtolower((string) $issue->environment)) {
            'production' => 'badge-error',
            'staging' => 'badge-warning',
            'local' => 'badge-info',
            default => 'badge-neutral',
        };

        $environmentCount = \Hewerthomn\ErrorTracker\Models\Issue::query()
            ->select('environment')
            ->whereNotNull('environment')
            ->distinct()
            ->count();

        $showEnvironmentInHeader = match (config('error-tracker.dashboard.show_environment_in_issue_header', 'auto')) {
            'auto' => $environmentCount > 1,
            default => (bool) config('error-tracker.dashboard.show_environment_in_issue_header', 'auto'),
        };
    @endphp

    @include('error-tracker::partials.page-header', [
        'title' => $issue->title,
        'subtitle' => $issue->exception_class,
        'breadcrumbs' => [
            ['label' => 'Issues', 'url' => route('error-tracker.index')],
        ],
        'badges' => array_values(array_filter([
            ['label' => $issue->level, 'class' => $levelBadgeClass],
            ['label' => $issue->status, 'class' => $statusBadgeClass],
            $showEnvironmentInHeader
                ? ['label' => $issue->environment, 'class' => $environmentBadgeClass]
                : null,
        ])),
    ])

    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Summary</h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">First seen</span>
                    <div class="stat-value" style="font-size: 18px;">
                        {{ optional($issue->first_seen_at)?->format('d/m/Y') ?: '—' }}
                    </div>
                    <div class="stat-meta">
                        {{ optional($issue->first_seen_at)?->format('H:i:s') ?: '—' }}
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Last seen</span>
                    <div class="stat-value" style="font-size: 18px;">
                        {{ optional($issue->last_seen_at)?->format('d/m/Y') ?: '—' }}
                    </div>
                    <div class="stat-meta">
                        {{ optional($issue->last_seen_at)?->format('H:i:s') ?: '—' }}
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Total events</span>
                    <div class="stat-value">{{ number_format($issue->total_events) }}</div>
                    <div class="stat-meta">All recorded occurrences</div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Affected users</span>
                    <div class="stat-value">{{ number_format($issue->affected_users) }}</div>
                    <div class="stat-meta">Unique identified users</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Trend</h2>
            </div>

            <canvas id="issueTrendChart" height="120"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent events</h2>
        </div>

        @if ($issue->events->isNotEmpty())
            <table class="refined-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Message</th>
                        <th>Source</th>
                        <th>User</th>
                        <th>Occurred at</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($issue->events as $event)
                        @php
                            $eventLevelBadgeClass = match (strtolower((string) $event->level)) {
                                'error', 'critical', 'alert', 'emergency' => 'badge-error',
                                'warning' => 'badge-warning',
                                'info', 'notice' => 'badge-info',
                                'debug' => 'badge-neutral',
                                default => 'badge-neutral',
                            };

                            $eventSource = $event->route_name
                                ?: ($event->request_path ?: ($event->command_name ?: ($event->job_name ?: 'Unknown source')));
                        @endphp

                        <tr>
                            <td>
                                <div class="event-primary-line">
                                    <a href="{{ route('error-tracker.events.show', $event) }}" class="event-link">
                                        Event #{{ $event->id }}
                                    </a>
                                    <div class="table-cell-badges">
                                        <span class="badge {{ $eventLevelBadgeClass }}">{{ $event->level }}</span>
                                    </div>
                                </div>

                                <div class="inline-meta" title="{{ $event->uuid }}">
                                    UUID {{ \Illuminate\Support\Str::limit($event->uuid, 8, '') }}
                                </div>
                            </td>

                            <td>
                                <div class="table-cell-title">
                                    {{ \Illuminate\Support\Str::limit($event->message ?: 'No message available.', 110) }}
                                </div>

                                @if ($event->file || $event->line)
                                    <div class="table-cell-meta">
                                        {{ $event->file ?: '—' }}@if($event->line):{{ $event->line }}@endif
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="table-cell-title">
                                    {{ $eventSource }}
                                </div>

                                <div class="table-cell-meta">
                                    {{ $event->request_method ?: ($event->command_name ? 'CLI' : 'Request') }}
                                </div>
                            </td>

                            <td>
                                <div class="table-cell-title">
                                    {{ $event->user_label ?: 'Anonymous' }}
                                </div>

                                <div class="table-cell-meta">
                                    {{ $event->user_id ?: 'No user id' }}
                                </div>
                            </td>

                            <td>
                                <div class="table-cell-title">
                                    {{ optional($event->occurred_at)?->format('d/m/Y') ?: '—' }}
                                </div>

                                <div class="table-cell-meta">
                                    {{ optional($event->occurred_at)?->format('H:i:s') ?: '—' }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="kv-empty">No events found.</div>
        @endif
    </div>

    <dialog id="muteIssueDialog" class="modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <div class="section-label">Issue action</div>
                    <h2 class="modal-title">Mute issue</h2>
                    <div class="muted" style="margin-top: 6px;">
                        Temporarily hide repeated noise while keeping events recorded.
                    </div>
                </div>

                <form method="dialog">
                    <button type="submit" class="modal-close">×</button>
                </form>
            </div>

            <form method="POST" action="{{ route('error-tracker.issues.mute', $issue) }}">
                @csrf

                <div class="stack">
                    <div class="field-group">
                        <label for="muted_until" class="field-label">Muted until</label>
                        <input
                            id="muted_until"
                            type="datetime-local"
                            name="muted_until"
                            value="{{ old('muted_until') }}"
                        >
                        <div class="field-help">Leave empty to mute without an expiration date.</div>
                    </div>

                    <div class="field-group">
                        <label for="mute_reason" class="field-label">Reason</label>
                        <input
                            id="mute_reason"
                            type="text"
                            name="mute_reason"
                            placeholder="Optional reason"
                            value="{{ old('mute_reason') }}"
                        >
                    </div>
                </div>

                <div class="modal-footer">
                  <button
                      type="button"
                      class="btn btn-outline"
                      onclick="document.getElementById('muteIssueDialog').close()"
                  >
                      Cancel
                  </button>

                  <button type="submit" class="btn btn-purple">Submit mute</button>
              </div>
            </form>
        </div>
    </dialog>

    <script>
        const ctx = document.getElementById('issueTrendChart');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [{
                    label: 'Events',
                    data: @json($trendValues),
                    tension: 0.28,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#ffffff',
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)'
                        }
                    }
                }
            }
        });
    </script>
@endsection
