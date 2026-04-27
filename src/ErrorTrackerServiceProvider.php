<?php

namespace Hewerthomn\ErrorTracker;

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Commands\AutoResolveCommand;
use Hewerthomn\ErrorTracker\Commands\PruneCommand;
use Hewerthomn\ErrorTracker\Contracts\ExceptionRecorder;
use Hewerthomn\ErrorTracker\Services\ErrorTrackerManager;
use Hewerthomn\ErrorTracker\Services\FeedbackService;
use Hewerthomn\ErrorTracker\Services\FingerprintGenerator;
use Hewerthomn\ErrorTracker\Services\IssueNotifier;
use Hewerthomn\ErrorTracker\Services\IssueStatusService;
use Hewerthomn\ErrorTracker\Services\SensitiveDataSanitizer;
use Hewerthomn\ErrorTracker\Services\TrendAggregator;
use Hewerthomn\ErrorTracker\Support\ErrorPageState;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ErrorTrackerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('error-tracker')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigration('create_error_tracker_issues_table')
            ->hasMigration('create_error_tracker_events_table')
            ->hasMigration('create_error_tracker_issue_trends_table')
            ->hasMigration('create_error_tracker_feedback_table')
            ->hasMigration('add_user_id_to_error_tracker_feedback_table')
            ->hasMigration('add_resolution_metadata_to_error_tracker_issues_table')
            ->hasCommand(AutoResolveCommand::class)
            ->hasCommand(PruneCommand::class)
            ->hasInstallCommand(function ($command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SensitiveDataSanitizer::class);
        $this->app->singleton(FingerprintGenerator::class);
        $this->app->singleton(TrendAggregator::class);
        $this->app->singleton(IssueStatusService::class);
        $this->app->singleton(IssueNotifier::class);
        $this->app->singleton(FeedbackService::class);
        $this->app->singleton(ErrorPageState::class);

        $this->app->singleton(ErrorTrackerManager::class, function ($app) {
            return new ErrorTrackerManager(
                $app->make(FingerprintGenerator::class),
                $app->make(SensitiveDataSanitizer::class),
                $app->make(TrendAggregator::class),
                $app->make(IssueStatusService::class),
            );
        });

        $this->app->singleton(ExceptionRecorder::class, ErrorTrackerManager::class);

        $this->app->singleton(RecordThrowableAction::class, function ($app) {
            return new RecordThrowableAction(
                $app->make(ExceptionRecorder::class),
                $app->make(IssueNotifier::class),
            );
        });

        $this->app->singleton(ErrorTracker::class, function ($app) {
            return new ErrorTracker(
                $app->make(RecordThrowableAction::class)
            );
        });
    }
}
