<?php

use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

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
        ->assertSee('error_tracker_issues table exists')
        ->assertSee('error_tracker_events table exists')
        ->assertSee('error_tracker_issue_trends table exists')
        ->assertSee('error_tracker_issue_notifications table exists')
        ->assertSee('error_tracker_feedback table exists')
        ->assertSee('resolved_by_type column exists')
        ->assertSee('resolved_reason column exists')
        ->assertSee('command error-tracker:auto-resolve available')
        ->assertSee('command error-tracker:prune available')
        ->assertSee('config cached')
        ->assertSee('diagnostics-health-table', false)
        ->assertSee('config-badge', false)
        ->assertSee('OK')
        ->assertSee('Available')
        ->assertSee(app()->configurationIsCached() ? 'Yes' : 'No');
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
