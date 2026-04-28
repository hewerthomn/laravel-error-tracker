<?php

namespace Hewerthomn\ErrorTracker\Support\Diagnostics;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnosticsRunner
{
    protected const FIX_CONFIG = 'php artisan vendor:publish --tag=error-tracker-config';

    protected const FIX_MIGRATIONS = "php artisan vendor:publish --tag=error-tracker-migrations\nphp artisan migrate";

    /**
     * @return array<int, DiagnosticCheck>
     */
    public function run(): array
    {
        return [
            $this->configCheck(),
            $this->tableCheck('error_tracker_issues', 'Core issue storage'),
            $this->tableCheck('error_tracker_events', 'Event capture'),
            $this->tableCheck('error_tracker_issue_trends', 'Dashboard trends'),
            $this->tableCheck('error_tracker_feedback', 'User feedback'),
            $this->tableCheck('error_tracker_issue_notifications', 'Notification Cooldown'),
            $this->columnCheck('error_tracker_issues', 'resolved_by_type', 'Auto Resolve metadata'),
            $this->columnCheck('error_tracker_issues', 'resolved_reason', 'Auto Resolve metadata'),
            $this->commandCheck('error-tracker:prune'),
            $this->commandCheck('error-tracker:auto-resolve'),
            $this->commandCheck('error-tracker:doctor'),
            $this->configCacheCheck(),
            $this->configBooleanCheck('auto_resolve', (bool) config('error-tracker.auto_resolve.enabled', false)),
            $this->configBooleanCheck('notifications', (bool) config('error-tracker.notifications.enabled', true)),
            $this->notificationCooldownCheck(),
            $this->configBooleanCheck('feedback', (bool) config('error-tracker.feedback.enabled', false)),
        ];
    }

    protected function configCheck(): DiagnosticCheck
    {
        $loaded = is_array(config('error-tracker'));
        $published = file_exists(config_path('error-tracker.php'));

        if ($loaded) {
            return new DiagnosticCheck(
                key: 'config.loaded',
                label: 'config/error-tracker.php loaded',
                status: 'ok',
                target: 'config/error-tracker.php',
                description: $published
                    ? 'Published config file is present.'
                    : 'Config is loaded from package defaults. Publish it before customizing package settings.',
                fixCommand: $published ? null : self::FIX_CONFIG,
                required: false,
                feature: 'Configuration',
            );
        }

        return new DiagnosticCheck(
            key: 'config.loaded',
            label: 'config/error-tracker.php missing',
            status: 'missing',
            target: 'config/error-tracker.php',
            description: 'The Error Tracker config was not loaded.',
            fixCommand: self::FIX_CONFIG,
            required: true,
            feature: 'Configuration',
        );
    }

    protected function tableCheck(string $table, string $feature): DiagnosticCheck
    {
        $exists = $this->hasTable($table);

        return new DiagnosticCheck(
            key: 'table.'.$table,
            label: $table.' table',
            status: $exists ? 'ok' : 'missing',
            target: $table,
            description: $exists
                ? 'Required database table exists.'
                : 'Missing table: '.$table,
            fixCommand: $exists ? null : self::FIX_MIGRATIONS,
            required: true,
            feature: $feature,
        );
    }

    protected function columnCheck(string $table, string $column, string $feature): DiagnosticCheck
    {
        $exists = $this->hasColumn($table, $column);
        $target = $table.'.'.$column;

        return new DiagnosticCheck(
            key: 'column.'.$target,
            label: $target.' column',
            status: $exists ? 'ok' : 'missing',
            target: $target,
            description: $exists
                ? 'Required database column exists.'
                : 'Missing column: '.$target,
            fixCommand: $exists ? null : self::FIX_MIGRATIONS,
            required: true,
            feature: $feature,
        );
    }

    protected function commandCheck(string $command): DiagnosticCheck
    {
        $exists = array_key_exists($command, Artisan::all());

        return new DiagnosticCheck(
            key: 'command.'.$command,
            label: $command.' command',
            status: $exists ? 'ok' : 'missing',
            target: $command,
            description: $exists
                ? 'Artisan command is available.'
                : 'Artisan command is not registered.',
            required: true,
            feature: 'Commands',
        );
    }

    protected function configCacheCheck(): DiagnosticCheck
    {
        $cached = app()->configurationIsCached();

        return new DiagnosticCheck(
            key: 'config.cached',
            label: 'config cache '.($cached ? 'enabled' : 'disabled'),
            status: 'info',
            target: $cached ? 'cached' : 'not cached',
            description: $cached
                ? 'Laravel is using cached configuration.'
                : 'Laravel configuration is not cached.',
            required: false,
            feature: 'Configuration',
        );
    }

    protected function configBooleanCheck(string $feature, bool $enabled): DiagnosticCheck
    {
        return new DiagnosticCheck(
            key: 'config.'.$feature.'.enabled',
            label: $feature.' '.($enabled ? 'enabled' : 'disabled'),
            status: 'info',
            target: 'error-tracker.'.$feature.'.enabled',
            description: $enabled ? 'Feature is enabled.' : 'Feature is disabled.',
            required: false,
            feature: str_replace('_', ' ', $feature),
        );
    }

    protected function notificationCooldownCheck(): DiagnosticCheck
    {
        $minutes = (int) config('error-tracker.notifications.cooldown_minutes', 30);
        $maxPerHour = (int) config('error-tracker.notifications.max_per_issue_per_hour', 3);

        return new DiagnosticCheck(
            key: 'config.notifications.cooldown',
            label: 'notification cooldown configured',
            status: 'info',
            target: 'error-tracker.notifications.cooldown_minutes',
            description: 'Cooldown: '.$minutes.' minute(s). Max per issue per hour: '.$maxPerHour.'.',
            required: false,
            feature: 'Notification Cooldown',
        );
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::connection($this->connectionName())->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            $schema = Schema::connection($this->connectionName());

            return $schema->hasTable($table) && $schema->hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    protected function connectionName(): ?string
    {
        $connection = config('error-tracker.database.connection');

        return is_string($connection) && trim($connection) !== '' ? $connection : null;
    }
}
