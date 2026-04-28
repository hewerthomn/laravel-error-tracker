<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Hewerthomn\ErrorTracker\Support\Diagnostics\DiagnosticCheck;
use Hewerthomn\ErrorTracker\Support\Diagnostics\DiagnosticsRunner;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    protected $signature = 'error-tracker:doctor
        {--json : Output diagnostics as JSON}
        {--fail-on-missing : Return a failure exit code when required resources are missing}';

    protected $description = 'Run Error Tracker upgrade and configuration diagnostics';

    public function handle(DiagnosticsRunner $diagnostics): int
    {
        $checks = $diagnostics->run();
        $missingRequired = collect($checks)
            ->contains(fn (DiagnosticCheck $check): bool => $check->required && $check->status === 'missing');

        if ($this->option('json')) {
            $this->output->write(json_encode([
                'ok' => ! $missingRequired,
                'missing_required' => $missingRequired,
                'checks' => array_map(
                    fn (DiagnosticCheck $check): array => $check->toArray(),
                    $checks,
                ),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

            return $missingRequired ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Error Tracker diagnostics');
        $this->newLine();

        foreach ($this->groupChecks($checks) as $section => $sectionChecks) {
            $this->renderSection($section, $sectionChecks);
        }

        $this->newLine();

        if ($missingRequired) {
            $this->warn('Diagnostics finished with missing required resources.');

            return self::FAILURE;
        }

        $this->info('Diagnostics finished without missing required resources.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, DiagnosticCheck>  $checks
     * @return array<string, array<int, DiagnosticCheck>>
     */
    protected function groupChecks(array $checks): array
    {
        $groups = [
            'Database' => [],
            'Commands' => [],
            'Configuration' => [],
            'Scheduler' => [],
            'Features' => [],
        ];

        foreach ($checks as $check) {
            $groups[$this->sectionFor($check)][] = $check;
        }

        return array_filter($groups);
    }

    protected function sectionFor(DiagnosticCheck $check): string
    {
        if (str_starts_with($check->key, 'table.') || str_starts_with($check->key, 'column.')) {
            return 'Database';
        }

        if (str_starts_with($check->key, 'command.')) {
            return 'Commands';
        }

        if (str_starts_with($check->key, 'scheduler.')) {
            return 'Scheduler';
        }

        if ($check->feature === 'Configuration' || $check->key === 'config.cached') {
            return 'Configuration';
        }

        return 'Features';
    }

    /**
     * @param  array<int, DiagnosticCheck>  $checks
     */
    protected function renderSection(string $section, array $checks): void
    {
        $this->components->twoColumnDetail('<fg=yellow;options=bold>'.$section.'</>', '');
        $this->table(['Status', 'Check', 'Target', 'Required'], array_map(
            fn (DiagnosticCheck $check): array => [
                strtoupper($check->status),
                $check->label,
                $check->target,
                $check->required ? 'yes' : 'no',
            ],
            $checks,
        ));

        foreach ($checks as $check) {
            $this->renderCheck($check);
        }

        $this->newLine();
    }

    protected function renderCheck(DiagnosticCheck $check): void
    {
        $prefix = strtoupper($check->status);

        $this->line($prefix.' '.$check->label);
        $this->line('Target: '.$check->target);

        if ($check->feature !== null) {
            $this->line('Required by: '.$check->feature);
        }

        $this->line($check->description);

        if ($check->fixCommand !== null && in_array($check->status, ['missing', 'warning', 'info'], true)) {
            $this->line($check->status === 'missing' ? 'Run:' : 'Hint:');

            foreach (explode("\n", $check->fixCommand) as $command) {
                $this->line($command);
            }
        }

        $this->newLine();
    }
}
