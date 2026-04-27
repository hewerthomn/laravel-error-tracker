@extends('error-tracker::layout', ['title' => config('error-tracker.dashboard.title_prefix').' - '.$appName])

@section('content')
    @include('error-tracker::partials.page-header', [
        'title' => 'Error Tracker',
        'subtitle' => $appName,
        'breadcrumbs' => [],
        'actions' => 'error-tracker::dashboard.partials.period-filter',
    ])

    <div class="issues-workspace">
        <aside class="issues-sidebar" aria-label="Issue filters">
            @include('error-tracker::dashboard.partials.filter-sidebar')
        </aside>

        <main class="issues-main">
            @include('error-tracker::dashboard.partials.topbar')

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
