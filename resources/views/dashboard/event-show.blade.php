@extends('error-tracker::layout', ['title' => config('error-tracker.dashboard.title_prefix').' - '.config('app.name')])

@section('content')
    @php
        $levelBadgeClass = match (strtolower((string) $event->level)) {
            'error', 'critical', 'alert', 'emergency' => 'badge-error',
            'warning' => 'badge-warning',
            'info', 'notice' => 'badge-info',
            'debug' => 'badge-neutral',
            default => 'badge-neutral',
        };

        $statusBadgeClass = match (strtolower((string) $event->issue->status)) {
            'open' => 'badge-info',
            'resolved' => 'badge-success',
            'ignored' => 'badge-warning',
            'muted' => 'badge-muted',
            default => 'badge-neutral',
        };

        $environmentBadgeClass = match (strtolower((string) $event->environment)) {
            'production' => 'badge-error',
            'staging' => 'badge-warning',
            'local' => 'badge-info',
            default => 'badge-neutral',
        };

        $sourceLabel = $event->route_name
            ?: ($event->request_path ?: ($event->command_name ?: ($event->job_name ?: 'Unknown source')));

        $traceFrames = is_array($event->trace_json) ? $event->trace_json : [];
        $headersData = is_array($event->headers_json) ? $event->headers_json : [];
        $contextData = is_array($event->context_json) ? $event->context_json : [];

        $prettyHeadersJson = json_encode($headersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $prettyContextJson = json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $renderStructuredData = function ($data, string $rootKey = 'root') use (&$renderStructuredData) {
            if ($data === null || $data === [] || $data === '') {
                return new \Illuminate\Support\HtmlString('<div class="kv-empty">No data available.</div>');
            }

            if (is_object($data)) {
                $data = (array) $data;
            }

            if (! is_array($data)) {
                return new \Illuminate\Support\HtmlString(
                    '<div class="kv-row">'.
                        '<div class="kv-key">'.e($rootKey).'</div>'.
                        '<div class="kv-value">'.e(is_bool($data) ? ($data ? 'true' : 'false') : (string) $data).'</div>'.
                    '</div>'
                );
            }

            $html = '<div class="kv-list">';

            foreach ($data as $key => $value) {
                $label = is_int($key) ? '#'.$key : (string) $key;

                if (is_array($value) || is_object($value)) {
                    $html .= '<details class="kv-group" open>';
                    $html .= '<summary>'.e($label).'</summary>';
                    $html .= '<div class="kv-group-body">';
                    $html .= $renderStructuredData($value, $label)->toHtml();
                    $html .= '</div>';
                    $html .= '</details>';
                    continue;
                }

                if ($value === null) {
                    $formatted = 'null';
                } elseif (is_bool($value)) {
                    $formatted = $value ? 'true' : 'false';
                } else {
                    $formatted = (string) $value;
                }

                $html .= '<div class="kv-row">';
                $html .= '<div class="kv-key">'.e($label).'</div>';
                $html .= '<div class="kv-value">'.e($formatted).'</div>';
                $html .= '</div>';
            }

            $html .= '</div>';

            return new \Illuminate\Support\HtmlString($html);
        };

        $resolvedByLabel = $event->issue->status === 'resolved'
            ? match ($event->issue->resolved_by_type) {
                'manual' => 'resolved manually',
                'auto' => 'resolved automatically',
                default => null,
            }
            : null;
    @endphp

    @include('error-tracker::partials.page-header', [
      'title' => 'Event #'.$event->id,
      'subtitle' => $event->issue->title,
      'breadcrumbs' => [
          ['label' => 'Issues', 'url' => route('error-tracker.index')],
          ['label' => 'Issue #'.$event->issue->id, 'url' => route('error-tracker.issues.show', $event->issue)],
      ],
      'badges' => [
          ['label' => $event->level, 'class' => $levelBadgeClass],
          ['label' => $event->issue->status, 'class' => $statusBadgeClass],
          ['label' => $event->environment, 'class' => $environmentBadgeClass],
      ],
  ])

    <div class="summary-grid-refined">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Summary</h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Occurred at</span>
                    <div class="stat-value" style="font-size: 18px;">
                        {{ optional($event->occurred_at)?->format('d/m/Y') ?: '—' }}
                    </div>
                    <div class="stat-meta">
                        {{ optional($event->occurred_at)?->format('H:i:s') ?: '—' }}
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Source</span>
                    <div class="stat-value" style="font-size: 18px;">
                        {{ \Illuminate\Support\Str::limit($sourceLabel, 28) }}
                    </div>
                    <div class="stat-meta">
                        {{ $event->request_method ?: ($event->command_name ? 'CLI' : 'Request') }}
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">User</span>
                    <div class="stat-value" style="font-size: 18px;">
                        {{ $event->user_label ?: 'Anonymous' }}
                    </div>
                    <div class="stat-meta">
                        {{ $event->user_id ?: 'No user id' }}
                    </div>
                </div>

                <div class="stat-card">
                    <span class="stat-label">Status code</span>
                    <div class="stat-value">
                        {{ $event->status_code ?: '—' }}
                    </div>
                    <div class="stat-meta">
                        HTTP response status
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Issue reference</h2>
            </div>

            <div class="compact-issue-reference">
                <div class="panel-soft">
                    <div class="field-group">
                        <span class="field-label">Issue</span>
                        <div style="font-weight: 700;">{{ $event->issue->title }}</div>
                    </div>

                    <div class="field-group" style="margin-top: 12px;">
                        <span class="field-label">Exception class</span>
                        <div>{{ $event->issue->exception_class ?: '—' }}</div>
                    </div>
                </div>

                <div class="compact-issue-meta">
                    <span class="badge {{ $statusBadgeClass }}">{{ $event->issue->status }}</span>
                    <span class="muted">Issue #{{ $event->issue->id }}</span>
                    <span class="muted">{{ number_format($event->issue->total_events) }} events</span>
                    @if ($resolvedByLabel)
                        <span class="muted">{{ $resolvedByLabel }}</span>
                    @endif
                </div>

                <div>
                    <a href="{{ route('error-tracker.issues.show', $event->issue) }}" class="btn btn-outline btn-sm">
                        Open issue
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="tabs-container">
        <div class="tabs-shell">
            <div class="tabs-nav" data-tabs-nav>
                <button type="button" class="tab-button is-active" data-tab-target="event-overview">Overview</button>
                <button type="button" class="tab-button" data-tab-target="event-stack">Stack trace</button>
                <button type="button" class="tab-button" data-tab-target="event-headers">Headers</button>
                <button type="button" class="tab-button" data-tab-target="event-context">Context</button>

                @if ($event->feedback)
                    <button type="button" class="tab-button" data-tab-target="event-feedback">User feedback</button>
                @endif
            </div>

            <div class="tabs-content">
                <div id="event-overview" class="tab-panel is-active">
                <div class="stack">
                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title">Message</h2>
                        </div>

                        <div class="panel-soft" style="font-size: 16px; line-height: 1.7;">
                            {{ $event->message ?: 'No message available.' }}
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="card" style="margin-bottom: 0;">
                            <div class="card-header">
                                <h2 class="card-title">Execution details</h2>
                            </div>

                            <div class="stack">
                                <div class="panel-soft">
                                    <div class="field-group">
                                        <span class="field-label">File</span>
                                        <div>{{ $event->file ?: '—' }}</div>
                                    </div>

                                    <div class="field-group" style="margin-top: 12px;">
                                        <span class="field-label">Line</span>
                                        <div>{{ $event->line ?: '—' }}</div>
                                    </div>
                                </div>

                                <div class="panel-soft">
                                    <div class="field-group">
                                        <span class="field-label">URL</span>
                                        <div>{{ $event->url ?: '—' }}</div>
                                    </div>

                                    <div class="field-group" style="margin-top: 12px;">
                                        <span class="field-label">Route</span>
                                        <div>{{ $event->route_name ?: '—' }}</div>
                                    </div>

                                    <div class="field-group" style="margin-top: 12px;">
                                        <span class="field-label">Request path</span>
                                        <div>{{ $event->request_path ?: '—' }}</div>
                                    </div>

                                    <div class="field-group" style="margin-top: 12px;">
                                        <span class="field-label">Request method</span>
                                        <div>{{ $event->request_method ?: '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card" style="margin-bottom: 0;">
                            <div class="card-header">
                                <h2 class="card-title">Runtime details</h2>
                            </div>

                            <div class="stack">
                                <div class="panel-soft">
                                    <div class="field-group">
                                        <span class="field-label">Environment</span>
                                        <div>{{ $event->environment ?: '—' }}</div>
                                    </div>

                                    <div class="field-group" style="margin-top: 12px;">
                                        <span class="field-label">Release</span>
                                        <div>{{ $event->release ?: '—' }}</div>
                                    </div>
                                </div>

                                <div class="panel-soft">
                                    <div class="field-group">
                                        <span class="field-label">Command</span>
                                        <div>{{ $event->command_name ?: '—' }}</div>
                                    </div>

                                    <div class="field-group" style="margin-top: 12px;">
                                        <span class="field-label">Job</span>
                                        <div>{{ $event->job_name ?: '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <div id="event-stack" class="tab-panel">
                @if (count($traceFrames) > 0)
                    <div class="stack">
                        @foreach ($traceFrames as $index => $frame)
                            <details class="stack-frame" @if ($index === 0) open @endif>
                                <summary>
                                    #{{ $index }}
                                    —
                                    {{ $frame['class'] ?? '' }}{{ $frame['type'] ?? '' }}{{ $frame['function'] ?? 'unknown' }}
                                </summary>

                                <div class="stack-frame-body">
                                    <div class="stack">
                                        <div class="panel-soft">
                                            <div class="field-group">
                                                <span class="field-label">File</span>
                                                <div>{{ $frame['file'] ?? '—' }}</div>
                                            </div>

                                            <div class="field-group" style="margin-top: 12px;">
                                                <span class="field-label">Line</span>
                                                <div>{{ $frame['line'] ?? '—' }}</div>
                                            </div>

                                            <div class="field-group" style="margin-top: 12px;">
                                                <span class="field-label">Callable</span>
                                                <div>{{ $frame['class'] ?? '' }}{{ $frame['type'] ?? '' }}{{ $frame['function'] ?? 'unknown' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @else
                    <div class="kv-empty">No stack trace frames available.</div>
                @endif
                </div>

                <div id="event-headers" class="tab-panel">
                <div class="json-toggle-row">
                    <button type="button" class="btn btn-outline btn-sm" data-json-toggle="headers">
                        Show raw JSON
                    </button>
                </div>

                <div id="headers-pretty" class="pretty-json-block">
                    {!! $renderStructuredData($headersData)->toHtml() !!}
                </div>

                <div id="headers-raw" class="raw-json-block">
                    <pre>{{ $prettyHeadersJson }}</pre>
                </div>
                </div>

                <div id="event-context" class="tab-panel">
                <div class="json-toggle-row">
                    <button type="button" class="btn btn-outline btn-sm" data-json-toggle="context">
                        Show raw JSON
                    </button>
                </div>

                <div id="context-pretty" class="pretty-json-block">
                    {!! $renderStructuredData($contextData)->toHtml() !!}
                </div>

                <div id="context-raw" class="raw-json-block">
                    <pre>{{ $prettyContextJson }}</pre>
                </div>
                </div>

            @if ($event->feedback)
                <div id="event-feedback" class="tab-panel">
                    <div class="grid grid-2">
                        <div class="panel-soft">
                            <div class="field-group">
                                <span class="field-label">Name</span>
                                <div>{{ $event->feedback->name ?: '—' }}</div>
                            </div>

                            <div class="field-group" style="margin-top: 12px;">
                                <span class="field-label">Email</span>
                                <div>{{ $event->feedback->email ?: '—' }}</div>
                            </div>

                            <div class="field-group" style="margin-top: 12px;">
                                <span class="field-label">User ID</span>
                                <div>{{ $event->feedback->user_id ?: '—' }}</div>
                            </div>
                        </div>

                        <div class="panel-soft">
                            <div class="field-group">
                                <span class="field-label">Message</span>
                                <div style="line-height: 1.6;">{{ $event->feedback->message }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-tab-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const container = button.closest('.tabs-shell');
                const targetId = button.getAttribute('data-tab-target');

                container.querySelectorAll('.tab-button').forEach((item) => {
                    item.classList.remove('is-active');
                });

                container.querySelectorAll('.tab-panel').forEach((panel) => {
                    panel.classList.remove('is-active');
                });

                button.classList.add('is-active');

                const targetPanel = container.querySelector('#' + targetId);

                if (targetPanel) {
                    targetPanel.classList.add('is-active');
                }
            });
        });

        document.querySelectorAll('[data-json-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.getAttribute('data-json-toggle');
                const pretty = document.getElementById(key + '-pretty');
                const raw = document.getElementById(key + '-raw');

                const rawVisible = raw.classList.contains('is-visible');

                if (rawVisible) {
                    raw.classList.remove('is-visible');
                    pretty.classList.remove('is-hidden');
                    button.textContent = 'Show raw JSON';
                } else {
                    raw.classList.add('is-visible');
                    pretty.classList.add('is-hidden');
                    button.textContent = 'Show structured view';
                }
            });
        });
    </script>
@endsection
