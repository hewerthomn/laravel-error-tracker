<?php

use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

it('allows authorized users to access the configuration page', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('Configuration')
        ->assertSee('read-only');
});

it('returns forbidden for unauthorized users', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => false);

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertForbidden();
});

it('renders the main diagnostics sections', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('General')
        ->assertSee('Capture')
        ->assertSee('Feedback')
        ->assertSee('Auto Resolve')
        ->assertSee('Stack Trace')
        ->assertSee('Notifications')
        ->assertSee('Cooldown minutes')
        ->assertSee('Max per issue per hour')
        ->assertSee('Retention')
        ->assertSee('Redaction')
        ->assertSee('Health checks')
        ->assertSee('Scheduler hints');
});

it('renders a visible configuration link on the issues index', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    /** @var TestCase $this */
    $this->get(route('error-tracker.index', ['period' => 'all']))
        ->assertOk()
        ->assertSee('Configuration')
        ->assertSee('href="'.route('error-tracker.configuration').'"', false);
});

it('masks notification secrets', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    config([
        'error-tracker.notifications.mail_to' => 'ops@example.com',
        'error-tracker.notifications.slack_webhook_url' => 'https://hooks.slack.com/services/T000/B000/secret-token',
        'error-tracker.notifications.slack_channel' => 'https://hooks.slack.com/services/T111/B111/old-secret',
    ]);

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('Mail recipient')
        ->assertSee('Slack webhook')
        ->assertSee('configured')
        ->assertDontSee('ops@example.com')
        ->assertDontSee('https://hooks.slack.com/services/T000/B000/secret-token')
        ->assertDontSee('old-secret');
});

it('renders health checks and config cached status', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('error_tracker_issues table')
        ->assertSee('error_tracker_events table')
        ->assertSee('error_tracker_issue_trends table')
        ->assertSee('error_tracker_issue_notifications table')
        ->assertSee('error_tracker_feedback table')
        ->assertSee('error_tracker_issues.resolved_by_type column')
        ->assertSee('error_tracker_issues.resolved_reason column')
        ->assertSee('error-tracker:auto-resolve command')
        ->assertSee('error-tracker:prune command')
        ->assertSee('error-tracker:doctor command')
        ->assertSee('config cache')
        ->assertSee('diagnostics-health-table', false)
        ->assertSee('config-badge', false)
        ->assertSee('OK')
        ->assertSee('Info')
        ->assertSee(app()->configurationIsCached() ? 'cached' : 'not cached');
});

it('shows missing diagnostics and fix command when notification history table is absent', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);
    Schema::dropIfExists('error_tracker_issue_notifications');

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('Missing table: error_tracker_issue_notifications')
        ->assertSee('Required by: Notification Cooldown')
        ->assertSee('php artisan vendor:publish --tag=error-tracker-migrations')
        ->assertSee('php artisan migrate')
        ->assertSee('Missing');
});

it('renders a breadcrumb link back to issues', function () {
    Gate::define('viewErrorTracker', fn ($user = null): bool => true);

    /** @var TestCase $this */
    $this->get(route('error-tracker.configuration'))
        ->assertOk()
        ->assertSee('Issues')
        ->assertSee('href="'.route('error-tracker.index').'"', false)
        ->assertSee('Back to issues');
});
