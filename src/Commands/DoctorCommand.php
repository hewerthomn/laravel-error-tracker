<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Hewerthomn\ErrorTracker\Support\Diagnostics\DiagnosticCheck;
use Hewerthomn\ErrorTracker\Support\Diagnostics\DiagnosticsRunner;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class DoctorCommand extends Command
{
    protected $signature = 'error-tracker:doctor
        {--json : Output diagnostics as JSON}
        {--fail-on-missing : Return a failure exit code when required resources are missing}';

    protected $description = 'Run Error Tracker upgrade and configuration diagnostics';

    public function handle(DiagnosticsRunner $diagnostics): int
    {
        $checks = $diagnostics->run();
        $summary = $this->summary($checks);
        $sections = $this->groupChecks($checks);
        $missingRequired = $summary['missing_required_count'] > 0;

        if ($this->option('json')) {
            $this->output->write(json_encode([
                'status' => $missingRequired ? 'missing' : 'ok',
                'summary' => $summary,
                'missing_required_count' => $summary['missing_required_count'],
                'warnings_count' => $summary['warning'],
                'sections' => $this->sectionsForJson($sections),
                'checks' => array_map(
                    fn (DiagnosticCheck $check): array => $this->checkForJson($check),
                    $checks,
                ),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

            return $this->exitCode($missingRequired);
        }

        $this->info('Error Tracker diagnostics');
        $this->newLine();
        $this->renderSummary($summary);

        foreach ($sections as $section => $sectionChecks) {
            $this->renderSection($section, $sectionChecks);
        }

        $this->newLine();

        if ($missingRequired) {
            $this->warn('Diagnostics finished with missing required resources.');

            return $this->exitCode(true);
        }

        $this->info('Diagnostics finished without missing required resources.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, DiagnosticCheck>  $checks
     * @return array{ok: int, missing: int, warning: int, info: int, total: int, missing_required_count: int}
     */
    protected function summary(array $checks): array
    {
        return [
            'ok' => collect($checks)->where('status', 'ok')->count(),
            'missing' => collect($checks)->where('status', 'missing')->count(),
            'warning' => collect($checks)->where('status', 'warning')->count(),
            'info' => collect($checks)->where('status', 'info')->count(),
            'total' => count($checks),
            'missing_required_count' => collect($checks)
                ->filter(fn (DiagnosticCheck $check): bool => $check->required && $check->status === 'missing')
                ->count(),
        ];
    }

    /**
     * @param  array{ok: int, missing: int, warning: int, info: int, total: int, missing_required_count: int}  $summary
     */
    protected function renderSummary(array $summary): void
    {
        $this->comment('Summary');
        $this->table(['OK', 'MISSING', 'WARNING', 'INFO', 'TOTAL'], [[
            (string) $summary['ok'],
            (string) $summary['missing'],
            (string) $summary['warning'],
            (string) $summary['info'],
            (string) $summary['total'],
        ]]);
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

        if ($section === 'Features') {
            $this->renderFeatureTable($checks);
        } elseif ($section === 'Scheduler') {
            $this->renderSchedulerTable($checks);
        } else {
            $this->renderDefaultTable($checks);
        }

        $details = array_values(array_filter(
            $checks,
            fn (DiagnosticCheck $check): bool => $this->shouldRenderDetails($check),
        ));

        foreach ($details as $check) {
            $this->renderCheckDetails($check);
        }

        $this->newLine();
    }

    /**
     * @param  array<int, DiagnosticCheck>  $checks
     */
    protected function renderDefaultTable(array $checks): void
    {
        $this->table(['Status', 'Check', 'Target', 'Required'], array_map(
            fn (DiagnosticCheck $check): array => [
                strtoupper($check->status),
                $check->label,
                $check->target,
                $check->required ? 'yes' : 'no',
            ],
            $checks,
        ));
    }

    /**
     * @param  array<int, DiagnosticCheck>  $checks
     */
    protected function renderFeatureTable(array $checks): void
    {
        $this->table(['Status', 'Feature', 'Value', 'Config key'], array_map(
            fn (DiagnosticCheck $check): array => [
                strtoupper($check->status),
                $check->label,
                $this->featureValue($check),
                $check->target,
            ],
            $checks,
        ));
    }

    /**
     * @param  array<int, DiagnosticCheck>  $checks
     */
    protected function renderSchedulerTable(array $checks): void
    {
        $this->table(['Status', 'Check', 'Hint'], array_map(
            fn (DiagnosticCheck $check): array => [
                strtoupper($check->status),
                $check->label,
                str_replace("\n", PHP_EOL, (string) $check->fixCommand),
            ],
            $checks,
        ));
    }

    protected function shouldRenderDetails(DiagnosticCheck $check): bool
    {
        return $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE
            || in_array($check->status, ['missing', 'warning'], true);
    }

    protected function renderCheckDetails(DiagnosticCheck $check): void
    {
        $this->line(strtoupper($check->status).' '.$check->label);
        $this->line('Target: '.$check->target);

        if ($check->feature !== null) {
            $this->line('Required by: '.$check->feature);
        }

        $this->line($check->description);

        if ($check->fixCommand !== null && $this->shouldRenderFixCommand($check)) {
            $this->line($check->status === 'missing' ? 'Run:' : 'Hint:');

            foreach (explode("\n", $check->fixCommand) as $command) {
                $this->line($command);
            }
        }

        $this->newLine();
    }

    protected function shouldRenderFixCommand(DiagnosticCheck $check): bool
    {
        return in_array($check->status, ['missing', 'warning'], true)
            || $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
    }

    protected function featureValue(DiagnosticCheck $check): string
    {
        if ($check->key === 'config.notifications.cooldown') {
            return (int) config('error-tracker.notifications.cooldown_minutes', 30)
                .' minutes / max '
                .(int) config('error-tracker.notifications.max_per_issue_per_hour', 3)
                .' per hour';
        }

        if (str_starts_with($check->key, 'config.') && str_ends_with($check->key, '.enabled')) {
            return str_contains($check->description, 'enabled') ? 'enabled' : 'disabled';
        }

        return $check->target;
    }

    /**
     * @param  array<string, array<int, DiagnosticCheck>>  $sections
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function sectionsForJson(array $sections): array
    {
        return collect($sections)
            ->map(fn (array $checks): array => array_map(
                fn (DiagnosticCheck $check): array => $this->checkForJson($check),
                $checks,
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkForJson(DiagnosticCheck $check): array
    {
        return [
            ...$check->toArray(),
            'value' => $this->sectionFor($check) === 'Features' ? $this->featureValue($check) : null,
            'required_by' => $check->feature,
        ];
    }

    protected function exitCode(bool $missingRequired): int
    {
        return $missingRequired && $this->option('fail-on-missing')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
