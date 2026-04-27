@php
    $selectedStatuses = $filters['statuses'] ?? [];
    $selectedLevels = $filters['levels'] ?? [];
    $activePeriod = $filters['period'] ?? 'all';
    $activeSort = $filters['sort'] ?? 'recent';
@endphp

<div class="issues-topbar">
    <form method="GET" action="{{ route('error-tracker.index') }}" class="issues-search-form">
        <input type="hidden" name="sort" value="{{ $activeSort }}">

        @if ($activePeriod !== 'all')
            <input type="hidden" name="period" value="{{ $activePeriod }}">
        @endif

        @if (($filters['environment'] ?? '') !== '')
            <input type="hidden" name="environment" value="{{ $filters['environment'] }}">
        @endif

        @foreach ($selectedStatuses as $selectedStatus)
            <input type="hidden" name="status{{ count($selectedStatuses) > 1 ? '[]' : '' }}" value="{{ $selectedStatus }}">
        @endforeach

        @foreach ($selectedLevels as $selectedLevel)
            <input type="hidden" name="level{{ count($selectedLevels) > 1 ? '[]' : '' }}" value="{{ $selectedLevel }}">
        @endforeach

        <label for="issues-search" class="sr-only">Search errors, paths, or messages</label>
        <div class="issues-search-control">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                <path d="m21 21-4.2-4.2m1.2-5.3a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            <input
                id="issues-search"
                type="search"
                name="search"
                placeholder="Search errors, paths, or messages..."
                value="{{ $filters['search'] ?? '' }}"
            >
        </div>
    </form>

    <nav class="sort-segmented" aria-label="Sort issues">
        @foreach ($sortOptions as $value => $label)
            @php
                $isActive = $activeSort === $value;
            @endphp

            <a
                href="{{ $queryString->url(['sort' => $value]) }}"
                class="sort-segment {{ $isActive ? 'is-active' : '' }}"
                @if ($isActive) aria-current="true" @endif
            >
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
