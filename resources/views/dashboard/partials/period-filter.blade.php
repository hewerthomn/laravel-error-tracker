@php
    $activePeriod = $filters['period'] ?? 'all';
@endphp

<nav class="period-segmented" aria-label="Filter issue period">
    @foreach ($periodOptions as $value => $label)
        @php
            $isActive = $activePeriod === $value;
        @endphp

        <a
            href="{{ $queryString->url(['period' => $value]) }}"
            class="period-segment {{ $isActive ? 'is-active' : '' }}"
            @if ($isActive) aria-current="true" @endif
        >
            {{ $label }}
        </a>
    @endforeach
</nav>
