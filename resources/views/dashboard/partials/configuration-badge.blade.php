@php
    $tone = $tone ?? 'neutral';
    $label = $label ?? '';
    $classes = match ($tone) {
        'success' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'danger' => 'bg-rose-100 text-rose-700 ring-rose-200',
        'warning' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'info' => 'bg-blue-100 text-blue-700 ring-blue-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<span class="config-badge is-{{ $tone }} inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-black leading-none ring-1 ring-inset {{ $classes }}">
    {{ $label }}
</span>
