<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Feedback;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Models\IssueNotification;
use Hewerthomn\ErrorTracker\Models\IssueTrend;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoCommand extends Command
{
    protected $signature = 'error-tracker:demo
        {--fresh : Remove previous demo data before creating it}
        {--purge : Remove demo data and stop}
        {--count=64 : Approximate number of demo events to create}
        {--with-feedback : Create demo user feedback}
        {--with-notifications : Create demo notification history when the table exists}
        {--with-resolved : Include resolved demo issues}
        {--force : Allow running in production}';

    protected $description = 'Create screenshot-friendly demo data for Error Tracker';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to create demo data in production without --force.');

            return self::FAILURE;
        }

        if ($this->option('purge')) {
            $deleted = $this->purgeDemoData();
            $this->info('Demo data purged.');
            $this->line('Deleted demo issues: '.$deleted);

            return self::SUCCESS;
        }

        if (! $this->hasTable('error_tracker_issues') || ! $this->hasTable('error_tracker_events')) {
            $this->error('Missing required Error Tracker tables. Run migrations before generating demo data.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->purgeDemoData();
        }

        $count = max(8, (int) $this->option('count'));
        $scenarios = $this->scenarios();
        $eventCounts = $this->eventCounts($count, $scenarios);

        $createdIssues = 0;
        $createdEvents = 0;

        foreach ($scenarios as $index => $scenario) {
            if (! $this->option('with-resolved') && ($scenario['status'] ?? null) === 'resolved') {
                // Resolved states are part of the default screenshot set, so this option is additive documentation.
            }

            $issue = $this->createIssue($scenario);
            $createdIssues++;
            $lastEvent = null;

            for ($i = 0; $i < $eventCounts[$index]; $i++) {
                $lastEvent = $this->createEvent($issue, $scenario, $i);
                $createdEvents++;
            }

            if ($lastEvent !== null) {
                $issue->forceFill([
                    'last_event_id' => $lastEvent->id,
                    'total_events' => $eventCounts[$index],
                    'affected_users' => min($eventCounts[$index], (int) ($scenario['affected_users'] ?? 4)),
                    'last_seen_at' => $lastEvent->occurred_at,
                ])->save();
            }

            $this->createTrends($issue, $scenario);

            if ($this->option('with-feedback') && ($scenario['feedback'] ?? false) && $lastEvent !== null) {
                $this->createFeedback($lastEvent);
            }

            if ($this->option('with-notifications') && ($scenario['notifications'] ?? false)) {
                $this->createNotifications($issue);
            }
        }

        $this->info('Demo data created.');
        $this->line('Demo issues: '.$createdIssues);
        $this->line('Demo events: '.$createdEvents);
        $this->line('Open the dashboard and filter for demo fingerprints with: demo:');

        return self::SUCCESS;
    }

    protected function purgeDemoData(): int
    {
        if (! $this->hasTable('error_tracker_issues')) {
            $this->warn('Skipping purge because error_tracker_issues does not exist.');

            return 0;
        }

        $issueIds = Issue::query()
            ->where('fingerprint', 'like', 'demo:%')
            ->pluck('id');

        if ($issueIds->isEmpty()) {
            return 0;
        }

        if ($this->hasTable('error_tracker_feedback') && $this->hasTable('error_tracker_events')) {
            Feedback::query()
                ->whereIn('event_id', Event::query()->whereIn('issue_id', $issueIds)->select('id'))
                ->delete();
        }

        if ($this->hasTable('error_tracker_issue_notifications')) {
            IssueNotification::query()
                ->whereIn('issue_id', $issueIds)
                ->delete();
        }

        if ($this->hasTable('error_tracker_issue_trends')) {
            IssueTrend::query()
                ->whereIn('issue_id', $issueIds)
                ->delete();
        }

        if ($this->hasTable('error_tracker_events')) {
            Event::query()
                ->whereIn('issue_id', $issueIds)
                ->delete();
        }

        return Issue::query()
            ->whereIn('id', $issueIds)
            ->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function scenarios(): array
    {
        return [
            [
                'fingerprint' => 'demo:checkout-critical-production',
                'title' => 'Payment checkout failed after gateway timeout',
                'level' => 'critical',
                'status' => 'open',
                'environment' => 'production',
                'exception_class' => 'App\\Exceptions\\PaymentGatewayTimeoutException',
                'message' => 'Checkout could not confirm payment before the gateway timeout.',
                'file' => 'app/Http/Controllers/CheckoutController.php',
                'line' => 84,
                'request_path' => '/checkout/confirm',
                'route_name' => 'checkout.confirm',
                'status_code' => 500,
                'release' => 'v2.8.3',
                'user_label' => 'Ada Lovelace',
                'affected_users' => 12,
                'weight' => 18,
                'notifications' => true,
            ],
            [
                'fingerprint' => 'demo:staging-webhook-error',
                'title' => 'Webhook signature mismatch',
                'level' => 'error',
                'status' => 'open',
                'environment' => 'staging',
                'exception_class' => 'App\\Exceptions\\InvalidWebhookSignature',
                'message' => 'The payment webhook signature could not be verified.',
                'file' => 'app/Services/PaymentGateway.php',
                'line' => 142,
                'request_path' => '/webhooks/payments',
                'route_name' => 'webhooks.payments',
                'status_code' => 401,
                'release' => 'v2.9.0-rc1',
                'user_label' => 'Webhook bot',
                'affected_users' => 1,
                'weight' => 10,
            ],
            [
                'fingerprint' => 'demo:local-warning-cache',
                'title' => 'Catalog cache warmed with stale prices',
                'level' => 'warning',
                'status' => 'open',
                'environment' => 'local',
                'exception_class' => 'App\\Exceptions\\StaleCatalogCache',
                'message' => 'A local catalog cache entry is older than the configured freshness window.',
                'file' => 'routes/web.php',
                'line' => 32,
                'request_path' => '/dev/catalog/preview',
                'route_name' => 'dev.catalog.preview',
                'status_code' => 200,
                'release' => 'local',
                'user_label' => 'Local developer',
                'affected_users' => 1,
                'weight' => 6,
            ],
            [
                'fingerprint' => 'demo:manual-resolved-invoice',
                'title' => 'Invoice PDF renderer exhausted memory',
                'level' => 'error',
                'status' => 'resolved',
                'environment' => 'production',
                'exception_class' => 'App\\Exceptions\\InvoiceRendererException',
                'message' => 'The invoice renderer exceeded the memory limit for a large corporate account.',
                'file' => 'app/Services/PaymentGateway.php',
                'line' => 201,
                'request_path' => '/billing/invoices/preview',
                'route_name' => 'billing.invoices.preview',
                'status_code' => 500,
                'release' => 'v2.8.1',
                'user_label' => 'Grace Hopper',
                'resolved_by_type' => 'manual',
                'resolved_reason' => 'Resolved after switching the invoice template to the streaming renderer.',
                'weight' => 8,
            ],
            [
                'fingerprint' => 'demo:auto-resolved-report',
                'title' => 'Nightly report lock contention',
                'level' => 'warning',
                'status' => 'resolved',
                'environment' => 'production',
                'exception_class' => 'App\\Exceptions\\ReportLockTimeout',
                'message' => 'The nightly report job could not acquire a processing lock.',
                'file' => 'app/Services/PaymentGateway.php',
                'line' => 64,
                'request_path' => null,
                'route_name' => null,
                'status_code' => null,
                'release' => 'v2.7.9',
                'user_label' => 'Scheduler',
                'resolved_by_type' => 'auto',
                'resolved_reason' => 'Automatically resolved after 14 days without new events.',
                'weight' => 5,
            ],
            [
                'fingerprint' => 'demo:muted-inventory-sync',
                'title' => 'Inventory sync intermittently unavailable',
                'level' => 'error',
                'status' => 'muted',
                'environment' => 'staging',
                'exception_class' => 'App\\Exceptions\\InventorySyncUnavailable',
                'message' => 'The inventory sandbox returned a temporary unavailable response.',
                'file' => 'app/Services/PaymentGateway.php',
                'line' => 119,
                'request_path' => '/admin/inventory/sync',
                'route_name' => 'admin.inventory.sync',
                'status_code' => 503,
                'release' => 'v2.9.0-rc1',
                'user_label' => 'Release manager',
                'mute_reason' => 'Known sandbox instability during release testing.',
                'weight' => 7,
            ],
            [
                'fingerprint' => 'demo:ignored-healthcheck',
                'title' => 'Synthetic health check timed out',
                'level' => 'warning',
                'status' => 'ignored',
                'environment' => 'local',
                'exception_class' => 'App\\Exceptions\\SyntheticHealthCheckTimeout',
                'message' => 'The synthetic health check endpoint timed out during a local debug session.',
                'file' => 'routes/web.php',
                'line' => 18,
                'request_path' => '/health/synthetic',
                'route_name' => 'health.synthetic',
                'status_code' => 504,
                'release' => 'local',
                'user_label' => 'Health monitor',
                'weight' => 4,
            ],
            [
                'fingerprint' => 'demo:feedback-profile-save',
                'title' => 'Profile save failed after validation',
                'level' => 'error',
                'status' => 'open',
                'environment' => 'production',
                'exception_class' => 'App\\Exceptions\\ProfileSaveException',
                'message' => 'The profile form passed validation but failed while saving preferences.',
                'file' => 'app/Http/Controllers/CheckoutController.php',
                'line' => 117,
                'request_path' => '/account/profile',
                'route_name' => 'account.profile.update',
                'status_code' => 500,
                'release' => 'v2.8.3',
                'user_label' => 'Katherine Johnson',
                'feedback' => true,
                'weight' => 12,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $scenarios
     * @return array<int, int>
     */
    protected function eventCounts(int $target, array $scenarios): array
    {
        $weightTotal = max(1, array_sum(array_map(fn (array $scenario): int => (int) ($scenario['weight'] ?? 1), $scenarios)));
        $counts = [];

        foreach ($scenarios as $scenario) {
            $counts[] = max(1, (int) round($target * ((int) ($scenario['weight'] ?? 1) / $weightTotal)));
        }

        $delta = $target - array_sum($counts);

        for ($index = 0; $delta > 0; $index++, $delta--) {
            $counts[$index % count($counts)]++;
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    protected function createIssue(array $scenario): Issue
    {
        $now = now();
        $attributes = [
            'fingerprint' => $scenario['fingerprint'],
            'title' => $scenario['title'],
            'level' => $scenario['level'],
            'status' => $scenario['status'],
            'environment' => $scenario['environment'],
            'exception_class' => $scenario['exception_class'],
            'message_sample' => $scenario['message'],
            'first_seen_at' => $now->copy()->subDays(6),
            'last_seen_at' => $now,
            'total_events' => 0,
            'affected_users' => 0,
            'muted_until' => $scenario['status'] === 'muted' ? $now->copy()->addDays(3) : null,
            'mute_reason' => $scenario['mute_reason'] ?? null,
            'resolved_at' => $scenario['status'] === 'resolved' ? $now->copy()->subDays(2) : null,
            'ignored_at' => $scenario['status'] === 'ignored' ? $now->copy()->subDay() : null,
        ];

        if ($this->hasColumn('error_tracker_issues', 'resolved_by_type')) {
            $attributes['resolved_by_type'] = $scenario['resolved_by_type'] ?? null;
        }

        if ($this->hasColumn('error_tracker_issues', 'resolved_reason')) {
            $attributes['resolved_reason'] = $scenario['resolved_reason'] ?? null;
        }

        return Issue::query()->updateOrCreate([
            'fingerprint' => $scenario['fingerprint'],
            'environment' => $scenario['environment'],
        ], $attributes);
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    protected function createEvent(Issue $issue, array $scenario, int $index): Event
    {
        $occurredAt = $this->eventTime($index);
        $feedbackToken = (string) Str::uuid();

        return $issue->events()->create([
            'uuid' => (string) Str::uuid(),
            'occurred_at' => $occurredAt,
            'level' => $scenario['level'],
            'exception_class' => $scenario['exception_class'],
            'message' => $scenario['message'],
            'file' => $scenario['file'],
            'line' => $scenario['line'],
            'request_method' => $scenario['request_path'] === null ? null : ($index % 3 === 0 ? 'POST' : 'GET'),
            'request_path' => $scenario['request_path'],
            'route_name' => $scenario['route_name'],
            'url' => $scenario['request_path'] === null ? null : 'https://demo-app.test'.$scenario['request_path'],
            'status_code' => $scenario['status_code'],
            'command_name' => $scenario['request_path'] === null ? 'reports:nightly' : null,
            'job_name' => $scenario['request_path'] === null ? 'App\\Jobs\\BuildNightlyReport' : null,
            'environment' => $scenario['environment'],
            'release' => $scenario['release'],
            'user_id' => 'demo-user-'.(($index % 9) + 1),
            'user_type' => 'App\\Models\\User',
            'user_label' => $scenario['user_label'],
            'ip_hash' => hash('sha256', 'demo-ip-'.$index),
            'trace_json' => $this->trace($scenario),
            'context_json' => [
                '_demo' => true,
                'cart_id' => 'cart_demo_'.str_pad((string) (($index % 17) + 1), 2, '0', STR_PAD_LEFT),
                'tenant' => 'northwind-demo',
                'feature_flag' => 'checkout-v2',
                'request_id' => 'req_demo_'.Str::lower(Str::random(10)),
            ],
            'headers_json' => [
                'user-agent' => 'Mozilla/5.0 Demo Screenshot Browser',
                'x-request-id' => 'req_demo_'.Str::lower(Str::random(10)),
            ],
            'feedback_token' => $feedbackToken,
        ]);
    }

    protected function eventTime(int $index): Carbon
    {
        if ($index < 24) {
            return now()->copy()->subHours($index)->subMinutes(($index * 7) % 55);
        }

        return now()->copy()->subDays(($index % 7) + 1)->subHours($index % 24);
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<int, array<string, mixed>>
     */
    protected function trace(array $scenario): array
    {
        return [
            [
                'file' => 'app/Http/Controllers/CheckoutController.php',
                'line' => 84,
                'class' => 'App\\Http\\Controllers\\CheckoutController',
                'type' => '->',
                'function' => 'confirm',
                'is_culprit' => $scenario['file'] === 'app/Http/Controllers/CheckoutController.php',
                'source_context' => $this->sourceContext(84, '$receipt = $this->payments->capture($request->validated());'),
            ],
            [
                'file' => 'app/Services/PaymentGateway.php',
                'line' => 142,
                'class' => 'App\\Services\\PaymentGateway',
                'type' => '->',
                'function' => 'capture',
                'is_culprit' => $scenario['file'] === 'app/Services/PaymentGateway.php',
                'source_context' => $this->sourceContext(142, 'throw new PaymentGatewayTimeoutException($response->message());'),
            ],
            [
                'file' => 'vendor/spatie/laravel-data/src/DataPipes/ValidatePropertiesDataPipe.php',
                'line' => 51,
                'class' => 'Spatie\\LaravelData\\DataPipes\\ValidatePropertiesDataPipe',
                'type' => '->',
                'function' => 'handle',
            ],
            [
                'file' => 'vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php',
                'line' => 46,
                'class' => 'Illuminate\\Routing\\ControllerDispatcher',
                'type' => '->',
                'function' => 'dispatch',
                'is_throwing_frame' => true,
            ],
            [
                'file' => 'vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php',
                'line' => 183,
                'class' => 'Illuminate\\Pipeline\\Pipeline',
                'type' => '->',
                'function' => 'then',
            ],
            [
                'file' => 'routes/web.php',
                'line' => 32,
                'function' => '{closure}',
                'is_culprit' => $scenario['file'] === 'routes/web.php',
                'source_context' => $this->sourceContext(32, "Route::post('/checkout/confirm', [CheckoutController::class, 'confirm']);"),
            ],
        ];
    }

    /**
     * @return array{start_line: int, end_line: int, error_line: int, lines: array<int, array{number: int, code: string, is_error_line: bool}>}
     */
    protected function sourceContext(int $line, string $errorCode): array
    {
        return [
            'start_line' => $line - 2,
            'end_line' => $line + 2,
            'error_line' => $line,
            'lines' => [
                ['number' => $line - 2, 'code' => 'if ($request->expectsJson()) {', 'is_error_line' => false],
                ['number' => $line - 1, 'code' => '    $this->authorizeRequest($request);', 'is_error_line' => false],
                ['number' => $line, 'code' => $errorCode, 'is_error_line' => true],
                ['number' => $line + 1, 'code' => '    return response()->json($payload);', 'is_error_line' => false],
                ['number' => $line + 2, 'code' => '}', 'is_error_line' => false],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    protected function createTrends(Issue $issue, array $scenario): void
    {
        if (! $this->hasTable('error_tracker_issue_trends')) {
            $this->warn('Skipping trends because error_tracker_issue_trends does not exist.');

            return;
        }

        $base = (int) max(1, $scenario['weight'] ?? 1);

        for ($hour = 23; $hour >= 0; $hour--) {
            $count = max(1, (int) round(($base / 4) + (($hour % 5) * 0.8)));
            $this->upsertTrend($issue, now()->copy()->subHours($hour)->startOfHour(), 'hour', $count);
        }

        for ($day = 6; $day >= 0; $day--) {
            $count = max(1, (int) round($base * (1 + (($day % 3) / 4))));
            $this->upsertTrend($issue, now()->copy()->subDays($day)->startOfDay(), 'day', $count);
        }
    }

    protected function upsertTrend(Issue $issue, Carbon $bucketStart, string $granularity, int $eventsCount): void
    {
        IssueTrend::query()->updateOrCreate([
            'issue_id' => $issue->id,
            'bucket_start' => $bucketStart,
            'bucket_granularity' => $granularity,
        ], [
            'events_count' => $eventsCount,
        ]);
    }

    protected function createFeedback(Event $event): void
    {
        if (! $this->hasTable('error_tracker_feedback')) {
            $this->warn('Skipping feedback because error_tracker_feedback does not exist.');

            return;
        }

        Feedback::query()->updateOrCreate([
            'event_id' => $event->id,
        ], [
            'feedback_token' => $event->feedback_token,
            'user_id' => 'demo-user-8',
            'name' => 'Katherine Johnson',
            'email' => 'katherine@example.test',
            'message' => 'I clicked save after changing my billing address and saw the reference code on the error page.',
            'url' => 'https://demo-app.test/account/profile',
            'user_agent' => 'Mozilla/5.0 Demo Screenshot Browser',
        ]);
    }

    protected function createNotifications(Issue $issue): void
    {
        if (! $this->hasTable('error_tracker_issue_notifications')) {
            $this->warn('Skipping notifications because error_tracker_issue_notifications does not exist.');

            return;
        }

        foreach (['mail', 'slack'] as $index => $channel) {
            IssueNotification::query()->create([
                'issue_id' => $issue->id,
                'channel' => $channel,
                'reason' => $index === 0 ? 'new_issue' : 'regression',
                'sent_at' => now()->copy()->subMinutes(45 - ($index * 20)),
            ]);
        }
    }

    protected function hasTable(string $table): bool
    {
        return Schema::connection($this->connectionName())->hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $schema = Schema::connection($this->connectionName());

        return $schema->hasTable($table) && $schema->hasColumn($table, $column);
    }

    protected function connectionName(): ?string
    {
        $connection = config('error-tracker.database.connection');

        return is_string($connection) && trim($connection) !== '' ? $connection : null;
    }
}
