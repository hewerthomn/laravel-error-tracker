@php
    $status = strtolower((string) $issue->status);
    $statusLabel = match ($status) {
        'open' => 'OPEN',
        'resolved' => 'RESOLVED',
        'ignored' => 'IGNORED',
        'muted' => 'MUTED',
        default => strtoupper($status ?: 'UNKNOWN'),
    };
    $statusClass = match ($status) {
        'open' => 'is-open',
        'resolved' => 'is-resolved',
        'ignored' => 'is-ignored',
        'muted' => 'is-muted',
        default => 'is-neutral',
    };
    $levelClass = match (strtolower((string) $issue->level)) {
        'error', 'critical', 'alert', 'emergency' => 'badge-error',
        'warning' => 'badge-warning',
        'info', 'notice' => 'badge-info',
        'debug' => 'badge-neutral',
        default => 'badge-neutral',
    };
    $environmentClass = match (strtolower((string) $issue->environment)) {
        'production' => 'badge-error',
        'staging' => 'badge-warning',
        'local' => 'badge-info',
        default => 'badge-neutral',
    };
    $pathNormalizer = app(\Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer::class);
    $lastEventFile = $pathNormalizer->normalize($issue->lastEvent?->file);
    $location = $lastEventFile
        ? $lastEventFile.($issue->lastEvent?->line ? ':'.$issue->lastEvent->line : '')
        : ($issue->exception_class ?: 'No frame available');
    $resolvedByLabel = $status === 'resolved'
        ? match ($issue->resolved_by_type) {
            'manual' => 'resolved manually',
            'auto' => 'resolved automatically',
            default => null,
        }
        : null;
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

<article class="issue-card-item">
    <div class="issue-card-content">
        <div class="issue-card-meta-row">
            <span class="issue-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            <span>{{ optional($issue->last_seen_at)?->diffForHumans() ?: 'Never seen' }}</span>

            @if ($showEnvironmentBadge)
                <span class="badge {{ $environmentClass }}">{{ $issue->environment }}</span>
            @endif

            <span class="badge {{ $levelClass }}">{{ $issue->level }}</span>

            @if ($resolvedByLabel)
                <span>{{ $resolvedByLabel }}</span>
            @endif
        </div>

        <a href="{{ route('error-tracker.issues.show', $issue) }}" class="issue-card-title">
            {{ $issue->title }}
        </a>

        <p class="issue-card-message">
            {{ $issue->message_sample ?: $issue->exception_class ?: 'No message sample recorded.' }}
        </p>

        <div class="issue-card-footer-row">
            <span class="issue-location-chip" title="{{ $location }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                    <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h4l2 2h7A2.5 2.5 0 0 1 21 9.5v7A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                </svg>
                {{ \Illuminate\Support\Str::limit($location, 76) }}
            </span>

            @if ($trendMax > 0)
                <div class="issue-card-trend" aria-label="Events trend over the last 24 hours">
                    @foreach ($trendValues as $trendValue)
                        <span
                            style="height: {{ max(3, (int) round(($trendValue / $trendMax) * 22)) }}px;"
                            title="{{ $trendValue }} {{ \Illuminate\Support\Str::plural('event', $trendValue) }}"
                        ></span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="issue-card-side">
        <div class="issue-card-count">
            <strong>{{ number_format($issue->total_events) }}</strong>
            <span>{{ \Illuminate\Support\Str::plural('event', (int) $issue->total_events) }}</span>
        </div>

        <div class="issue-card-actions" aria-label="Quick issue actions">
            @if ($status === 'open')
                <form method="POST" action="{{ route('error-tracker.issues.resolve', $issue) }}">
                    @csrf
                    <button type="submit" class="issue-action-button primary">Resolve</button>
                </form>

                <form method="POST" action="{{ route('error-tracker.issues.ignore', $issue) }}">
                    @csrf
                    <button type="submit" class="issue-action-button">Ignore</button>
                </form>
            @elseif ($status === 'muted')
                <form method="POST" action="{{ route('error-tracker.issues.unmute', $issue) }}">
                    @csrf
                    <button type="submit" class="issue-action-button primary">Unmute</button>
                </form>
            @elseif (in_array($status, ['resolved', 'ignored'], true))
                <form method="POST" action="{{ route('error-tracker.issues.reopen', $issue) }}">
                    @csrf
                    <button type="submit" class="issue-action-button primary">Reopen</button>
                </form>
            @endif
        </div>
    </div>
</article>
