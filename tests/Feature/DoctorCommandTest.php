<?php

use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

it('shows ok when all diagnostic resources exist', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:doctor')
        ->expectsOutputToContain('OK error_tracker_issues table')
        ->expectsOutputToContain('OK error_tracker_events table')
        ->expectsOutputToContain('OK error_tracker_issue_trends table')
        ->expectsOutputToContain('OK error_tracker_feedback table')
        ->expectsOutputToContain('OK error_tracker_issue_notifications table')
        ->expectsOutputToContain('OK error-tracker:doctor command')
        ->expectsOutputToContain('INFO config cache disabled')
        ->assertExitCode(Command::SUCCESS);
});

it('shows missing and migration fix commands when notification history table does not exist', function () {
    Schema::dropIfExists('error_tracker_issue_notifications');

    /** @var TestCase $this */
    $this->artisan('error-tracker:doctor')
        ->expectsOutputToContain('MISSING error_tracker_issue_notifications table')
        ->expectsOutputToContain('Required by: Notification Cooldown')
        ->expectsOutputToContain('php artisan vendor:publish --tag=error-tracker-migrations')
        ->expectsOutputToContain('php artisan migrate')
        ->assertExitCode(Command::FAILURE);
});

it('mentions doctor in the install command output', function () {
    /** @var TestCase $this */
    $this->artisan('error-tracker:install')
        ->expectsConfirmation('Would you like to run the migrations now?', 'no')
        ->expectsOutputToContain('php artisan migrate')
        ->expectsOutputToContain('php artisan error-tracker:doctor')
        ->expectsOutputToContain('php artisan vendor:publish --tag=error-tracker-migrations')
        ->assertExitCode(Command::SUCCESS);
});
