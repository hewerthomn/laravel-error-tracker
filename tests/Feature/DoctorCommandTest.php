<?php

use Hewerthomn\ErrorTracker\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('shows section tables and does not duplicate ok check details by default', function () {
    $exitCode = Artisan::call('error-tracker:doctor');
    $output = Artisan::output();

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($output)->toContain('Error Tracker diagnostics')
        ->and($output)->toContain('Summary')
        ->and($output)->toContain('Database')
        ->and($output)->toContain('Commands')
        ->and($output)->toContain('Configuration')
        ->and($output)->toContain('Scheduler')
        ->and($output)->toContain('Features')
        ->and($output)->toContain('Diagnostics finished without missing required resources.')
        ->and(substr_count($output, 'OK error_tracker_issues table'))->toBe(0)
        ->and($output)->not->toContain('Required database table exists.');
});

it('shows missing details and migration fix commands by default', function () {
    Schema::dropIfExists('error_tracker_issue_notifications');

    $exitCode = Artisan::call('error-tracker:doctor');
    $output = Artisan::output();

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($output)->toContain('MISSING')
        ->and($output)->toContain('error_tracker_issue_notifications table')
        ->and($output)->toContain('Required by: Notification Cooldown')
        ->and($output)->toContain('Missing table: error_tracker_issue_notifications')
        ->and($output)->toContain('php artisan vendor:publish --tag=error-tracker-migrations')
        ->and($output)->toContain('php artisan migrate');
});

it('shows ok and info details in verbose mode', function () {
    $exitCode = Artisan::call('error-tracker:doctor', [
        '--verbose' => true,
    ]);
    $output = Artisan::output();

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($output)->toContain('OK error_tracker_issues table')
        ->and($output)->toContain('Target: error_tracker_issues')
        ->and($output)->toContain('Required database table exists.')
        ->and($output)->toContain('INFO Maintenance scheduler')
        ->and($output)->toContain("Schedule::command('error-tracker:auto-resolve')->daily();");
});

it('returns valid json diagnostics without ascii tables', function () {
    $exitCode = Artisan::call('error-tracker:doctor', [
        '--json' => true,
    ]);
    $output = Artisan::output();
    $payload = json_decode($output, true);

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($payload)->toBeArray()
        ->and($payload['status'])->toBe('ok')
        ->and($payload['summary'])->toBeArray()
        ->and($payload['sections'])->toBeArray()
        ->and($payload['checks'])->toBeArray()
        ->and($payload['missing_required_count'])->toBe(0)
        ->and($payload['warnings_count'])->toBe(0)
        ->and($output)->not->toContain('+')
        ->and($output)->not->toContain('| Status');
});

it('fails with fail on missing when required resources are missing', function () {
    Schema::dropIfExists('error_tracker_issue_notifications');

    $exitCode = Artisan::call('error-tracker:doctor', [
        '--fail-on-missing' => true,
    ]);

    expect($exitCode)->toBe(Command::FAILURE);
});

it('fails with json and fail on missing when required resources are missing', function () {
    Schema::dropIfExists('error_tracker_issue_notifications');

    $exitCode = Artisan::call('error-tracker:doctor', [
        '--json' => true,
        '--fail-on-missing' => true,
    ]);

    $payload = json_decode(Artisan::output(), true);

    expect($exitCode)->toBe(Command::FAILURE)
        ->and($payload['status'])->toBe('missing')
        ->and($payload['missing_required_count'])->toBe(1);
});

it('renders feature values separately from config keys', function () {
    $exitCode = Artisan::call('error-tracker:doctor');
    $output = Artisan::output();

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($output)->toContain('Feature')
        ->and($output)->toContain('Value')
        ->and($output)->toContain('Config key')
        ->and($output)->toContain('Auto Resolve')
        ->and($output)->toContain('disabled')
        ->and($output)->toContain('error-tracker.auto_resolve.enabled')
        ->and($output)->toContain('Notification Cooldown')
        ->and($output)->toContain('minutes / max');
});

it('shows scheduler as info and not as an error', function () {
    $exitCode = Artisan::call('error-tracker:doctor');
    $output = Artisan::output();

    expect($exitCode)->toBe(Command::SUCCESS)
        ->and($output)->toContain('INFO')
        ->and($output)->toContain('Maintenance scheduler')
        ->and($output)->toContain("Schedule::command('error-tracker:auto-resolve')->daily();")
        ->and($output)->toContain("Schedule::command('error-tracker:prune')->daily();")
        ->and($output)->not->toContain('MISSING Maintenance scheduler');
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
