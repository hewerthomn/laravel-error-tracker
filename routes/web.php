<?php

use Hewerthomn\ErrorTracker\Http\Controllers\ConfigurationController;
use Hewerthomn\ErrorTracker\Http\Controllers\DashboardController;
use Hewerthomn\ErrorTracker\Http\Controllers\EventController;
use Hewerthomn\ErrorTracker\Http\Controllers\ExternalIssueController;
use Hewerthomn\ErrorTracker\Http\Controllers\FeedbackController;
use Hewerthomn\ErrorTracker\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

$path = config('error-tracker.route.path', 'error-tracker');
$middleware = config('error-tracker.route.middleware', ['web']);
$gate = config('error-tracker.route.gate', 'viewErrorTracker');
$feedbackThrottle = 'throttle:'.config('error-tracker.feedback.rate_limit', '5,1');

Route::middleware($middleware)
    ->prefix($path)
    ->as('error-tracker.')
    ->group(function () use ($feedbackThrottle) {
        Route::post('/feedback/{token}', [FeedbackController::class, 'store'])
            ->middleware($feedbackThrottle)
            ->name('feedback.store');
    });

Route::middleware(array_merge($middleware, ["can:{$gate}"]))
    ->prefix($path)
    ->as('error-tracker.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/configuration', [ConfigurationController::class, 'show'])->name('configuration');
        Route::get('/issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

        Route::post('/issues/{issue}/resolve', [IssueController::class, 'resolve'])->name('issues.resolve');
        Route::post('/issues/{issue}/reopen', [IssueController::class, 'reopen'])->name('issues.reopen');
        Route::post('/issues/{issue}/ignore', [IssueController::class, 'ignore'])->name('issues.ignore');
        Route::post('/issues/{issue}/mute', [IssueController::class, 'mute'])->name('issues.mute');
        Route::post('/issues/{issue}/unmute', [IssueController::class, 'unmute'])->name('issues.unmute');

        Route::post('/issues/{issue}/external-issues/github', [ExternalIssueController::class, 'createGithub'])->name('issues.external-issues.github.store');
        Route::post('/issues/{issue}/external-issues/link', [ExternalIssueController::class, 'link'])->name('issues.external-issues.link');
        Route::post('/issues/{issue}/external-issues/{externalIssue}/sync', [ExternalIssueController::class, 'sync'])->name('issues.external-issues.sync');
        Route::delete('/issues/{issue}/external-issues/{externalIssue}', [ExternalIssueController::class, 'destroy'])->name('issues.external-issues.destroy');
    });
