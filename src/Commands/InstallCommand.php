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
        $this->newLine();
        $this->comment('Next recommended commands:');
        $this->line('php artisan migrate');
        $this->line('php artisan error-tracker:doctor');
        $this->newLine();
        $this->comment('After composer update, publish new migrations and run diagnostics:');
        $this->line('php artisan vendor:publish --tag=error-tracker-migrations');
        $this->line('php artisan migrate');
        $this->line('php artisan error-tracker:doctor');

        return self::SUCCESS;
    }
}
