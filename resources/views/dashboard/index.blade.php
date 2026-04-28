@extends('error-tracker::layout', ['title' => config('error-tracker.dashboard.title_prefix').' - '.$appName])

@section('content')
    @include('error-tracker::partials.page-header', [
        'title' => 'Error Tracker',
        'titleUrl' => route('error-tracker.index'),
        'subtitle' => $appName,
        'breadcrumbs' => [],
        'actions' => 'error-tracker::dashboard.partials.index-actions',
    ])

    <div class="issues-workspace">
        <aside class="issues-sidebar" aria-label="Issue filters">
            @include('error-tracker::dashboard.partials.filter-sidebar')
        </aside>

        <main class="issues-main">
            @include('error-tracker::dashboard.partials.topbar')

            @if (! empty($activeFilterChips))
                <div class="active-filter-bar" aria-label="Active filters">
                    <div class="active-filter-chips">
                        @foreach ($activeFilterChips as $chip)
                            <span class="active-filter-chip">
                                <span>{{ $chip['label'] }}</span>
                                <strong>{{ $chip['value'] }}</strong>
                            </span>
                        @endforeach
                    </div>

                    <a href="{{ $clearFiltersUrl }}" class="clear-filters-link">Clear filters</a>
                </div>
            @endif

            <div class="issue-card-list" aria-label="Issues">
                @forelse ($issues as $issue)
                    @include('error-tracker::dashboard.partials.issue-card', ['issue' => $issue])
                @empty
                    <div class="issues-empty-state">
                        <h2>No issues found.</h2>
                        <p>Adjust the filters or search terms to broaden the current view.</p>
                    </div>
                @endforelse
            </div>

            <div class="issues-pagination">
                {{ $issues->links() }}
            </div>
        </main>
    </div>
@endsection
