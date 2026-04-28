<?php

namespace Hewerthomn\ErrorTracker;

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Commands\AutoResolveCommand;
use Hewerthomn\ErrorTracker\Commands\DoctorCommand;
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
use Hewerthomn\ErrorTracker\Support\StackFrameClassifier;
use Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer;
use Hewerthomn\ErrorTracker\Support\StackTrace\SourceContextReader;
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
            ->hasMigration('create_error_tracker_issue_notifications_table')
            ->hasMigration('create_error_tracker_feedback_table')
            ->hasMigration('add_user_id_to_error_tracker_feedback_table')
            ->hasMigration('add_resolution_metadata_to_error_tracker_issues_table')
            ->hasCommand(AutoResolveCommand::class)
            ->hasCommand(DoctorCommand::class)
            ->hasCommand(PruneCommand::class)
            ->hasInstallCommand(function ($command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->endWith(function ($command): void {
                        $command->newLine();
                        $command->comment('Next recommended commands:');
                        $command->line('php artisan migrate');
                        $command->line('php artisan error-tracker:doctor');
                        $command->newLine();
                        $command->comment('After composer update, publish new migrations and run diagnostics:');
                        $command->line('php artisan vendor:publish --tag=error-tracker-migrations');
                        $command->line('php artisan migrate');
                        $command->line('php artisan error-tracker:doctor');
                    });
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
        $this->app->singleton(PathNormalizer::class);
        $this->app->singleton(SourceContextReader::class);
        $this->app->singleton(StackFrameClassifier::class);

        $this->app->singleton(ErrorTrackerManager::class, function ($app) {
            return new ErrorTrackerManager(
                $app->make(FingerprintGenerator::class),
                $app->make(SensitiveDataSanitizer::class),
                $app->make(TrendAggregator::class),
                $app->make(IssueStatusService::class),
                $app->make(PathNormalizer::class),
                $app->make(SourceContextReader::class),
                $app->make(StackFrameClassifier::class),
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
