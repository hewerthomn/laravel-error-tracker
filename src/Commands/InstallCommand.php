<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'error-tracker:install';

    protected $description = 'Install the Error Tracker package';

    public function handle(): int
    {
        $this->info('Error Tracker installed successfully.');
        $this->comment('Publish config and migrations, then update bootstrap/app.php.');

        return self::SUCCESS;
    }
}
