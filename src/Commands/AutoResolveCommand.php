<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Hewerthomn\ErrorTracker\Actions\AutoResolveIssuesAction;
use Illuminate\Console\Command;

class AutoResolveCommand extends Command
{
    protected $signature = 'error-tracker:auto-resolve
        {--dry-run : Display what would be resolved without updating issues}
        {--days= : Temporarily override error-tracker.auto_resolve.after_days}';

    protected $description = 'Automatically resolve stale Error Tracker issues';

    public function handle(AutoResolveIssuesAction $action): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $days = $this->resolveDaysOption();

        if ($days === false) {
            return self::FAILURE;
        }

        if ($days !== null) {
            config(['error-tracker.auto_resolve.after_days' => $days]);
        }

        $result = $action->handle($isDryRun, $days);

        $this->info('Starting Error Tracker auto resolve...');
        $this->line('Dry run: '.($isDryRun ? 'yes' : 'no'));
        $this->line('Cutoff: '.$result['cutoff']->toDateTimeString());

        if (! $result['enabled']) {
            $this->warn('Auto resolve is disabled.');

            return self::SUCCESS;
        }

        $this->line('Eligible issues: '.$result['found']);

        if ($isDryRun) {
            $this->info('Dry run finished. No issues were changed.');

            return self::SUCCESS;
        }

        $this->info('Auto resolve finished.');
        $this->line('Resolved issues: '.$result['resolved']);

        return self::SUCCESS;
    }

    protected function resolveDaysOption(): int|false|null
    {
        $value = $this->option('days');

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || ! ctype_digit($value)) {
            $this->error('The --days option must be a non-negative integer.');

            return false;
        }

        return (int) $value;
    }
}
