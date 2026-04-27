<div class="dashboard-header-actions">
    <a href="{{ route('error-tracker.configuration') }}" class="diagnostics-link">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
            <path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z" stroke="currentColor" stroke-width="1.8" />
            <path d="M19.4 15a1.6 1.6 0 0 0 .32 1.76l.06.06a1.94 1.94 0 0 1-2.74 2.74l-.06-.06a1.6 1.6 0 0 0-1.76-.32 1.6 1.6 0 0 0-.98 1.48V21a1.94 1.94 0 0 1-3.88 0v-.09A1.6 1.6 0 0 0 9.4 19.4a1.6 1.6 0 0 0-1.76.32l-.06.06a1.94 1.94 0 0 1-2.74-2.74l.06-.06A1.6 1.6 0 0 0 5.22 15a1.6 1.6 0 0 0-1.48-.98H3.5a1.94 1.94 0 0 1 0-3.88h.09A1.6 1.6 0 0 0 5.1 9.18a1.6 1.6 0 0 0-.32-1.76l-.06-.06a1.94 1.94 0 0 1 2.74-2.74l.06.06a1.6 1.6 0 0 0 1.76.32h.08a1.6 1.6 0 0 0 .98-1.48V3.5a1.94 1.94 0 0 1 3.88 0v.09a1.6 1.6 0 0 0 .98 1.48 1.6 1.6 0 0 0 1.76-.32l.06-.06a1.94 1.94 0 0 1 2.74 2.74l-.06.06A1.6 1.6 0 0 0 19.4 9.2v.08a1.6 1.6 0 0 0 1.48.98H21a1.94 1.94 0 0 1 0 3.88h-.09A1.6 1.6 0 0 0 19.4 15Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
        </svg>
        Configuration
    </a>

    @include('error-tracker::dashboard.partials.period-filter')
</div>
