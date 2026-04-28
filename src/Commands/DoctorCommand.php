<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Hewerthomn\ErrorTracker\Support\Diagnostics\DiagnosticCheck;
use Hewerthomn\ErrorTracker\Support\Diagnostics\DiagnosticsRunner;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    protected $signature = 'error-tracker:doctor';

    protected $description = 'Run Error Tracker upgrade and configuration diagnostics';

    public function handle(DiagnosticsRunner $diagnostics): int
    {
        $this->info('Error Tracker diagnostics');
        $this->newLine();

        $checks = $diagnostics->run();

        foreach ($checks as $check) {
            $this->renderCheck($check);
        }

        $missingRequired = collect($checks)
            ->contains(fn (DiagnosticCheck $check): bool => $check->required && $check->status === 'missing');

        $this->newLine();

        if ($missingRequired) {
            $this->warn('Diagnostics finished with missing required resources.');

            return self::FAILURE;
        }

        $this->info('Diagnostics finished without missing required resources.');

        return self::SUCCESS;
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

        if ($check->status === 'missing' && $check->fixCommand !== null) {
            $this->line('Run:');

            foreach (explode("\n", $check->fixCommand) as $command) {
                $this->line($command);
            }
        }

        $this->newLine();
    }
}
