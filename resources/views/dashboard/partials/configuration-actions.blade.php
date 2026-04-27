<div class="dashboard-header-actions">
    <a href="{{ route('error-tracker.index') }}" class="diagnostics-link">
        Back to issues
    </a>

    @include('error-tracker::dashboard.partials.configuration-badge', [
        'label' => 'read-only',
        'tone' => 'info',
    ])
</div>
