@php
    $selectedStatuses = $filters['statuses'] ?? [];
    $selectedLevels = $filters['levels'] ?? [];
    $activePeriod = $filters['period'] ?? 'all';
    $activeEnvironment = $filters['environment'] ?? '';
    $statusFilterOptions = ['all' => 'All'] + $statusOptions;
    $levelFilterOptions = ['all' => 'All'] + $levelOptions;
    $environmentLinkLimit = 8;
@endphp

<div class="filter-sidebar-stack">
    <section class="filter-sidebar-section">
        <h2 class="filter-sidebar-heading">Status</h2>

        <div class="filter-sidebar-options">
            @foreach ($statusFilterOptions as $value => $label)
                @php
                    $isActive = $value === 'all' ? $selectedStatuses === [] : $selectedStatuses === [$value];
                @endphp

                <a
                    href="{{ $queryString->url(['status' => $value]) }}"
                    class="filter-sidebar-link {{ $isActive ? 'is-active' : '' }}"
                    @if ($isActive) aria-current="true" @endif
                >
                    <span>{{ $label }}</span>
                    <span class="filter-sidebar-count">{{ number_format($statusCounts[$value] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="filter-sidebar-section">
        <h2 class="filter-sidebar-heading">Level</h2>

        <div class="filter-sidebar-options">
            @foreach ($levelFilterOptions as $value => $label)
                @php
                    $isActive = $value === 'all' ? $selectedLevels === [] : $selectedLevels === [$value];
                @endphp

                <a
                    href="{{ $queryString->url(['level' => $value]) }}"
                    class="filter-sidebar-link {{ $isActive ? 'is-active' : '' }}"
                    @if ($isActive) aria-current="true" @endif
                >
                    <span>{{ $label }}</span>
                    <span class="filter-sidebar-count">{{ number_format($levelCounts[$value] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="filter-sidebar-section">
        <h2 class="filter-sidebar-heading">Environment</h2>

        @if ($canFilterEnvironment)
            @if ($environmentOptions->count() <= $environmentLinkLimit)
                <div class="filter-sidebar-options">
                    <a
                        href="{{ $queryString->url(['environment' => null]) }}"
                        class="filter-sidebar-link {{ $activeEnvironment === '' ? 'is-active' : '' }}"
                        @if ($activeEnvironment === '') aria-current="true" @endif
                    >
                        <span>All</span>
                    </a>

                    @foreach ($environmentOptions as $environment)
                        @php
                            $isActive = $activeEnvironment === $environment;
                        @endphp

                        <a
                            href="{{ $queryString->url(['environment' => $environment]) }}"
                            class="filter-sidebar-link {{ $isActive ? 'is-active' : '' }}"
                            @if ($isActive) aria-current="true" @endif
                        >
                            <span>{{ ucfirst($environment) }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <form method="GET" action="{{ route('error-tracker.index') }}" class="filter-sidebar-form">
                    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'recent' }}">

                    @if ($activePeriod !== 'all')
                        <input type="hidden" name="period" value="{{ $activePeriod }}">
                    @endif

                    @if (($filters['search'] ?? '') !== '')
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif

                    @foreach ($selectedStatuses as $selectedStatus)
                        <input type="hidden" name="status{{ count($selectedStatuses) > 1 ? '[]' : '' }}" value="{{ $selectedStatus }}">
                    @endforeach

                    @foreach ($selectedLevels as $selectedLevel)
                        <input type="hidden" name="level{{ count($selectedLevels) > 1 ? '[]' : '' }}" value="{{ $selectedLevel }}">
                    @endforeach

                    <select name="environment" aria-label="Filter environment" onchange="this.form.submit()">
                        <option value="" @selected($activeEnvironment === '')>All environments</option>
                        @foreach ($environmentOptions as $environment)
                            <option value="{{ $environment }}" @selected($activeEnvironment === $environment)>
                                {{ ucfirst($environment) }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        @else
            <div class="filter-sidebar-muted-row">{{ $environmentFallbackLabel }}</div>
        @endif
    </section>
</div>
