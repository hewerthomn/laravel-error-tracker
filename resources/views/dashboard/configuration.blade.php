@extends('error-tracker::layout', ['title' => config('error-tracker.dashboard.title_prefix').' Configuration - '.config('app.name')])

@push('head')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            corePlugins: {
                preflight: false,
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .et-diagnostics .et-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .et-diagnostics .et-card-header {
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 12px 16px;
        }

        .et-diagnostics .et-card-title {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .et-diagnostics .et-card-subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .et-diagnostics .et-table {
            width: 100%;
            border-collapse: collapse;
        }

        .et-diagnostics .et-table th,
        .et-diagnostics .et-table td {
            border-top: 1px solid #f1f5f9;
            padding: 10px 16px;
            text-align: left;
            vertical-align: middle;
        }

        .et-diagnostics .et-table th {
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .et-diagnostics .et-code-block {
            margin: 0;
            overflow-x: auto;
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px 16px;
        }
    </style>
@endpush

@section('content')
    @php
        $sections = $diagnostics['sections'] ?? [];
        $healthChecks = $diagnostics['health_checks'] ?? [];
        $schedulerHints = $diagnostics['scheduler_hints'] ?? [];

        $badge = function (string $label, string $tone = 'neutral') {
            return view('error-tracker::dashboard.partials.configuration-badge', [
                'label' => $label,
                'tone' => $tone,
            ])->render();
        };

        $renderValue = function (array $row) use ($badge) {
            $value = $row['value'] ?? null;
            $type = $row['type'] ?? 'text';
            $status = $row['status'] ?? null;

            if ($type === 'boolean') {
                $enabled = (bool) $value;

                return new \Illuminate\Support\HtmlString($badge($enabled ? 'enabled' : 'disabled', $enabled ? 'success' : 'danger'));
            }

            if ($type === 'status') {
                $configured = $status === 'configured';

                return new \Illuminate\Support\HtmlString($badge((string) $value, $configured ? 'success' : 'neutral'));
            }

            if ($type === 'list') {
                $items = is_array($value) ? $value : [];

                if ($items === []) {
                    return new \Illuminate\Support\HtmlString('<span class="text-sm font-semibold text-slate-500">None</span>');
                }

                $html = '<div class="flex flex-wrap gap-1.5">';

                foreach ($items as $item) {
                    $html .= '<span class="max-w-full rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-bold text-slate-700 break-all">'.e((string) $item).'</span>';
                }

                $html .= '</div>';

                return new \Illuminate\Support\HtmlString($html);
            }

            if ($type === 'code') {
                return new \Illuminate\Support\HtmlString('<code class="inline-flex max-w-full rounded-md bg-slate-900 px-2 py-1 font-mono text-xs font-bold text-slate-100 break-words">'.e((string) $value).'</code>');
            }

            if ($value === null || $value === '') {
                return new \Illuminate\Support\HtmlString('<span class="text-sm font-semibold text-slate-500">Not configured</span>');
            }

            return new \Illuminate\Support\HtmlString('<span class="text-sm font-bold text-slate-900">'.e(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value).'</span>');
        };
    @endphp

    @include('error-tracker::partials.page-header', [
        'title' => 'Configuration',
        'subtitle' => config('app.name'),
        'breadcrumbs' => [
            ['label' => 'Issues', 'url' => route('error-tracker.index')],
            ['label' => 'Configuration'],
        ],
        'actions' => 'error-tracker::dashboard.partials.configuration-actions',
    ])

    <div class="et-diagnostics space-y-4">
        <section class="et-card rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="et-card-header border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <h2 class="et-card-title text-sm font-black text-slate-950">Health checks</h2>
                    <p class="et-card-subtitle mt-1 text-xs font-bold text-slate-500">Runtime visibility only</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="diagnostics-health-table et-table min-w-full">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Check</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Target</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Status</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Details</th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Fix</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($healthChecks as $check)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2.5 text-sm font-bold text-slate-900">{{ $check['label'] }}</td>
                                <td class="px-4 py-2.5">
                                    <code class="font-mono text-xs font-bold text-slate-600 break-all">{{ $check['detail'] ?: '—' }}</code>
                                </td>
                                <td class="px-4 py-2.5">
                                    @php
                                        $healthStatus = (string) ($check['status'] ?? 'unknown');
                                        $healthLabel = $healthStatus === 'ok' ? 'OK' : \Illuminate\Support\Str::headline($healthStatus);
                                    @endphp
                                    {!! $badge($healthLabel, (string) ($check['tone'] ?? 'neutral')) !!}
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="text-sm font-semibold text-slate-700">{{ $check['description'] ?? '—' }}</div>
                                    @if (! empty($check['feature']))
                                        <div class="mt-1 text-xs font-bold text-slate-500">Required by: {{ $check['feature'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if (! empty($check['fix_command']) && ($check['status'] ?? null) === 'missing')
                                        <pre class="m-0 whitespace-pre-wrap rounded-md bg-slate-950 px-2 py-1.5 text-xs font-bold leading-5 text-slate-100"><code>{{ $check['fix_command'] }}</code></pre>
                                    @else
                                        <span class="text-sm font-semibold text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="et-card rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="et-card-header border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <h2 class="et-card-title text-sm font-black text-slate-950">Scheduler hints</h2>
                    <p class="et-card-subtitle mt-1 text-xs font-bold text-slate-500">Scheduler registration is not auto-detected.</p>
                </div>
            </div>

            <pre class="et-code-block overflow-x-auto bg-slate-950 px-4 py-3 text-slate-100"><code class="font-mono text-xs font-bold leading-6">@foreach ($schedulerHints as $hint){{ $hint }}
@endforeach</code></pre>
        </section>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($sections as $section)
                <section class="et-card rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="et-card-header border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h2 class="et-card-title text-sm font-black text-slate-950">{{ $section['title'] }}</h2>
                    </div>

                    <dl class="m-0 divide-y divide-slate-100">
                        @foreach (($section['rows'] ?? []) as $row)
                            <div class="grid grid-cols-1 gap-1 px-4 py-2.5 sm:grid-cols-[minmax(160px,0.42fr)_minmax(0,1fr)] sm:gap-4">
                                <dt class="text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $row['label'] }}</dt>
                                <dd class="m-0 min-w-0 text-sm font-bold text-slate-900">
                                    {!! $renderValue($row)->toHtml() !!}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    </div>
@endsection
