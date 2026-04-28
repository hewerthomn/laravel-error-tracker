<?php

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Console\Command;

it('keeps the default install flow working', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:install')
        ->expectsConfirmation('Would you like to run the migrations now?', 'no')
        ->expectsOutputToContain('php artisan migrate')
        ->expectsOutputToContain('php artisan error-tracker:doctor')
        ->expectsOutputToContain('php artisan vendor:publish --tag=error-tracker-migrations')
        ->assertExitCode(Command::SUCCESS);
});

it('guided mode does not break without interaction', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:install', [
        '--guided' => true,
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('Selected Error Tracker setup')
        ->expectsOutputToContain('Suggested .env values')
        ->assertExitCode(Command::SUCCESS);
});

it('shows minimal preset configuration suggestions', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:install', [
        '--preset' => 'minimal',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('ERROR_TRACKER_FEEDBACK_ENABLED')
        ->expectsOutputToContain('false')
        ->expectsOutputToContain('ERROR_TRACKER_SMART_STACKTRACE_ENABLED')
        ->assertExitCode(Command::SUCCESS);
});

it('shows production preset configuration suggestions', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:install', [
        '--preset' => 'production',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('ERROR_TRACKER_NOTIFICATIONS_ENABLED')
        ->expectsOutputToContain('ERROR_TRACKER_NOTIFICATION_COOLDOWN_MINUTES')
        ->expectsOutputToContain('ERROR_TRACKER_NOTIFICATION_MAX_PER_ISSUE_PER_HOUR')
        ->assertExitCode(Command::SUCCESS);
});

it('can generate demo data at the end of install', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:install', [
        '--with-demo' => true,
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('Generating demo data')
        ->assertExitCode(Command::SUCCESS);

    expect(Issue::query()->where('fingerprint', 'like', 'demo:%')->exists())->toBeTrue();
});
