<?php

use Hewerthomn\ErrorTracker\Http\Controllers\DashboardController;
use Hewerthomn\ErrorTracker\Http\Controllers\EventController;
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
        Route::get('/issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

        Route::post('/issues/{issue}/resolve', [IssueController::class, 'resolve'])->name('issues.resolve');
        Route::post('/issues/{issue}/reopen', [IssueController::class, 'reopen'])->name('issues.reopen');
        Route::post('/issues/{issue}/ignore', [IssueController::class, 'ignore'])->name('issues.ignore');
        Route::post('/issues/{issue}/mute', [IssueController::class, 'mute'])->name('issues.mute');
        Route::post('/issues/{issue}/unmute', [IssueController::class, 'unmute'])->name('issues.unmute');
    });
