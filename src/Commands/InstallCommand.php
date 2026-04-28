<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'error-tracker:install
        {--guided : Run the guided installer}
        {--interactive : Alias for --guided}
        {--preset= : Apply a preset suggestion: local, production, minimal, or demo}
        {--with-demo : Run error-tracker:demo after installation}
        {--write-env : Write safe preset suggestions to .env}
        {--force : Allow sensitive actions in production}';

    protected $description = 'Install the Error Tracker package';

    /**
     * @var array<int, string>
     */
    protected array $validPresets = ['local', 'production', 'minimal', 'demo'];

    public function handle(): int
    {
        $guided = (bool) ($this->option('guided') || $this->option('interactive'));
        $preset = $this->normalizePreset($this->option('preset'));

        $this->info('Installing Error Tracker...');
        $this->publishResources();

        $settings = null;
        $runMigrations = false;
        $runDemo = (bool) $this->option('with-demo');

        if ($guided || $preset !== null) {
            $settings = $guided
                ? $this->guidedSettings($preset)
                : $this->presetSettings($preset);

            $runMigrations = (bool) ($settings['run_migrations'] ?? false);
            $runDemo = $runDemo || (bool) ($settings['run_demo'] ?? false);

            $this->renderSettingsSummary($settings);
            $this->renderEnvSuggestions($settings['env']);

            if ($this->option('write-env')) {
                $this->writeEnv($settings['env'], $guided);
            }
        }

        if (! $guided && $settings === null) {
            $runMigrations = $this->shouldRunMigrations();
        }

        if ($runMigrations) {
            $this->comment('Running migrations...');
            $this->call('migrate');
        }

        $this->renderNextSteps();

        if ($runDemo) {
            $this->runDemoCommand();
        }

        $this->info('Error Tracker installed successfully.');

        return self::SUCCESS;
    }

    protected function publishResources(): void
    {
        $this->comment('Publishing config...');
        $this->call('vendor:publish', [
            '--tag' => 'error-tracker-config',
        ]);

        $this->comment('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'error-tracker-migrations',
        ]);
    }

    protected function shouldRunMigrations(): bool
    {
        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->confirm('Would you like to run the migrations now?', false);
    }

    /**
     * @return array{
     *     preset: string,
     *     error_page: bool,
     *     feedback: bool,
     *     feedback_allow_guest: bool,
     *     auto_resolve: bool,
     *     auto_resolve_after_days: int,
     *     notifications: bool,
     *     notification_channels: array<int, string>,
     *     notification_cooldown: bool,
     *     smart_stacktrace: bool,
     *     database_connection: string|null,
     *     run_migrations: bool,
     *     run_demo: bool,
     *     env: array<string, string>
     * }
     */
    protected function guidedSettings(?string $preset): array
    {
        $preset ??= $this->input->isInteractive()
            ? $this->promptSelect('Which preset do you want to use?', $this->validPresets, 'local')
            : 'local';

        $settings = $this->presetSettings($preset);

        if (! $this->input->isInteractive()) {
            return $settings;
        }

        $settings['error_page'] = $this->promptConfirm('Enable custom error page?', $settings['error_page']);
        $settings['feedback'] = $this->promptConfirm('Enable user feedback?', $settings['feedback']);
        $settings['feedback_allow_guest'] = $this->promptConfirm('Allow guest feedback?', $settings['feedback_allow_guest']);
        $settings['auto_resolve'] = $this->promptConfirm('Enable auto resolve?', $settings['auto_resolve']);
        $settings['auto_resolve_after_days'] = (int) $this->promptText('Days before auto resolve', (string) $settings['auto_resolve_after_days']);
        $settings['notifications'] = $this->promptConfirm('Enable notifications?', $settings['notifications']);
        $settings['notification_channels'] = $settings['notifications']
            ? $this->promptMultiSelect('Notification channels', ['mail', 'slack'], $settings['notification_channels'])
            : [];
        $settings['notification_cooldown'] = $this->promptConfirm('Enable notification cooldown?', $settings['notification_cooldown']);
        $settings['smart_stacktrace'] = $this->promptConfirm('Enable smart stack trace?', $settings['smart_stacktrace']);

        $databaseMode = $this->promptSelect('Use the default database connection or a dedicated connection?', ['default', 'dedicated'], $settings['database_connection'] === null ? 'default' : 'dedicated');
        $settings['database_connection'] = $databaseMode === 'dedicated'
            ? $this->promptText('Dedicated connection name', $settings['database_connection'] ?? 'error_tracker')
            : null;

        $settings['run_migrations'] = $this->promptConfirm('Run migrations now?', false);
        $settings['run_demo'] = $this->promptConfirm('Generate demo data?', $settings['run_demo']);
        $settings['env'] = $this->envForSettings($settings);

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    protected function presetSettings(string $preset): array
    {
        $settings = match ($preset) {
            'minimal' => [
                'feedback' => false,
                'notifications' => false,
                'auto_resolve' => false,
                'error_page' => false,
                'smart_stacktrace' => true,
                'notification_cooldown' => false,
                'run_demo' => false,
            ],
            'production' => [
                'feedback' => true,
                'notifications' => true,
                'auto_resolve' => false,
                'error_page' => true,
                'smart_stacktrace' => true,
                'notification_cooldown' => true,
                'run_demo' => false,
            ],
            'demo' => [
                'feedback' => true,
                'notifications' => false,
                'auto_resolve' => true,
                'error_page' => true,
                'smart_stacktrace' => true,
                'notification_cooldown' => false,
                'run_demo' => true,
            ],
            default => [
                'feedback' => true,
                'notifications' => false,
                'auto_resolve' => false,
                'error_page' => true,
                'smart_stacktrace' => true,
                'notification_cooldown' => false,
                'run_demo' => false,
            ],
        };

        $settings = array_merge([
            'preset' => $preset,
            'feedback_allow_guest' => true,
            'auto_resolve_after_days' => 14,
            'notification_channels' => $settings['notifications'] ? ['mail'] : [],
            'database_connection' => null,
            'run_migrations' => false,
        ], $settings);

        $settings['env'] = $this->envForSettings($settings);

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    protected function envForSettings(array $settings): array
    {
        $env = [
            'ERROR_TRACKER_ERROR_PAGE_ENABLED' => $this->envBoolean((bool) $settings['error_page']),
            'ERROR_TRACKER_FEEDBACK_ENABLED' => $this->envBoolean((bool) $settings['feedback']),
            'ERROR_TRACKER_FEEDBACK_ALLOW_GUEST' => $this->envBoolean((bool) $settings['feedback_allow_guest']),
            'ERROR_TRACKER_AUTO_RESOLVE_ENABLED' => $this->envBoolean((bool) $settings['auto_resolve']),
            'ERROR_TRACKER_AUTO_RESOLVE_AFTER_DAYS' => (string) max(1, (int) $settings['auto_resolve_after_days']),
            'ERROR_TRACKER_NOTIFICATIONS_ENABLED' => $this->envBoolean((bool) $settings['notifications']),
            'ERROR_TRACKER_NOTIFICATION_CHANNELS' => implode(',', (array) $settings['notification_channels']),
            'ERROR_TRACKER_NOTIFICATION_COOLDOWN_MINUTES' => (bool) $settings['notification_cooldown'] ? '30' : '0',
            'ERROR_TRACKER_NOTIFICATION_MAX_PER_ISSUE_PER_HOUR' => (bool) $settings['notification_cooldown'] ? '3' : '0',
            'ERROR_TRACKER_SMART_STACKTRACE_ENABLED' => $this->envBoolean((bool) $settings['smart_stacktrace']),
        ];

        if (is_string($settings['database_connection']) && trim($settings['database_connection']) !== '') {
            $env['ERROR_TRACKER_DB_CONNECTION'] = trim($settings['database_connection']);
        }

        return $env;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function renderSettingsSummary(array $settings): void
    {
        $this->newLine();
        $this->comment('Selected Error Tracker setup');
        $this->table(['Setting', 'Value'], [
            ['Preset', $settings['preset']],
            ['Custom error page', $this->yesNo((bool) $settings['error_page'])],
            ['Feedback', $this->yesNo((bool) $settings['feedback'])],
            ['Guest feedback', $this->yesNo((bool) $settings['feedback_allow_guest'])],
            ['Auto resolve', $this->yesNo((bool) $settings['auto_resolve'])],
            ['Auto resolve days', (string) $settings['auto_resolve_after_days']],
            ['Notifications', $this->yesNo((bool) $settings['notifications'])],
            ['Notification channels', implode(', ', (array) $settings['notification_channels']) ?: '-'],
            ['Notification cooldown', $this->yesNo((bool) $settings['notification_cooldown'])],
            ['Smart stack trace', $this->yesNo((bool) $settings['smart_stacktrace'])],
            ['Database connection', $settings['database_connection'] ?: 'default'],
            ['Generate demo data', $this->yesNo((bool) $settings['run_demo'])],
        ]);
    }

    /**
     * @param  array<string, string>  $env
     */
    protected function renderEnvSuggestions(array $env): void
    {
        $this->comment('Suggested .env values');
        $this->table(['Variable', 'Value'], collect($env)->map(
            fn (string $value, string $key): array => [$key, $value]
        )->values()->all());
    }

    /**
     * @param  array<string, string>  $env
     */
    protected function writeEnv(array $env, bool $guided): void
    {
        $path = base_path('.env');

        if (! file_exists($path) || ! is_writable($path)) {
            $this->warn('Skipping .env update because '.base_path('.env').' is missing or not writable.');

            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->warn('Skipping .env update because the file could not be read.');

            return;
        }

        $lines = preg_split('/\R/', $contents) ?: [];
        $existing = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*([A-Z0-9_]+)=/', $line, $matches) === 1) {
                $existing[$matches[1]] = $line;
            }
        }

        $changed = false;

        foreach ($env as $key => $value) {
            $newLine = $key.'='.$value;

            if (! array_key_exists($key, $existing)) {
                $lines[] = $newLine;
                $changed = true;

                continue;
            }

            if ($existing[$key] === $newLine) {
                continue;
            }

            if (! $guided || ! $this->input->isInteractive()) {
                $this->warn($key.' already exists in .env; leaving existing value unchanged.');

                continue;
            }

            if (! $this->promptConfirm($key.' already exists. Replace it?', false)) {
                continue;
            }

            foreach ($lines as $index => $line) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'=/', $line) === 1) {
                    $lines[$index] = $newLine;
                    $changed = true;
                    break;
                }
            }
        }

        if (! $changed) {
            $this->line('.env already contains the selected Error Tracker values.');

            return;
        }

        file_put_contents($path, rtrim(implode(PHP_EOL, $lines), PHP_EOL).PHP_EOL);
        $this->info('.env updated.');
    }

    protected function runDemoCommand(): void
    {
        $this->newLine();
        $this->comment('Generating demo data...');

        $arguments = [
            '--fresh' => true,
            '--with-feedback' => true,
            '--with-resolved' => true,
        ];

        if ((bool) $this->option('force')) {
            $arguments['--force'] = true;
        }

        $exitCode = $this->call('error-tracker:demo', $arguments);

        if ($exitCode !== self::SUCCESS) {
            $this->warn('Demo data was not generated. Run php artisan error-tracker:demo after migrations are ready.');
        }
    }

    protected function renderNextSteps(): void
    {
        $this->newLine();
        $this->comment('Next recommended commands:');
        $this->line('php artisan migrate');
        $this->line('php artisan error-tracker:doctor');
        $this->newLine();
        $this->comment('After composer update, publish new migrations and run diagnostics:');
        $this->line('php artisan vendor:publish --tag=error-tracker-migrations');
        $this->line('php artisan migrate');
        $this->line('php artisan error-tracker:doctor');
    }

    protected function normalizePreset(mixed $preset): ?string
    {
        if (! is_string($preset) || trim($preset) === '') {
            return null;
        }

        $preset = trim($preset);

        if (! in_array($preset, $this->validPresets, true)) {
            $this->warn('Unknown preset "'.$preset.'"; falling back to local.');

            return 'local';
        }

        return $preset;
    }

    /**
     * @param  array<int, string>  $options
     */
    protected function promptSelect(string $label, array $options, string $default): string
    {
        if ($this->canUseLaravelPrompts() && function_exists('Laravel\\Prompts\\select')) {
            return (string) \Laravel\Prompts\select(
                label: $label,
                options: $options,
                default: $default,
            );
        }

        return (string) $this->choice($label, $options, $default);
    }

    /**
     * @param  array<int, string>  $options
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    protected function promptMultiSelect(string $label, array $options, array $default): array
    {
        if ($this->canUseLaravelPrompts() && function_exists('Laravel\\Prompts\\multiselect')) {
            $selected = \Laravel\Prompts\multiselect(
                label: $label,
                options: $options,
                default: $default,
            );

            return array_values(array_filter($selected, fn ($value): bool => is_string($value)));
        }

        $answer = $this->ask($label.' (comma separated)', implode(',', $default));

        return collect(explode(',', (string) $answer))
            ->map(fn (string $channel): string => trim($channel))
            ->filter(fn (string $channel): bool => in_array($channel, $options, true))
            ->values()
            ->all();
    }

    protected function promptConfirm(string $label, bool $default): bool
    {
        if ($this->canUseLaravelPrompts() && function_exists('Laravel\\Prompts\\confirm')) {
            return (bool) \Laravel\Prompts\confirm(
                label: $label,
                default: $default,
            );
        }

        return $this->confirm($label, $default);
    }

    protected function promptText(string $label, string $default): string
    {
        if ($this->canUseLaravelPrompts() && function_exists('Laravel\\Prompts\\text')) {
            return (string) \Laravel\Prompts\text(
                label: $label,
                default: $default,
            );
        }

        return (string) $this->ask($label, $default);
    }

    protected function canUseLaravelPrompts(): bool
    {
        return $this->input->isInteractive() && ! app()->runningUnitTests();
    }

    protected function envBoolean(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    protected function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }
}
