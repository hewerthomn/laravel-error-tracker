<?php

namespace Hewerthomn\ErrorTracker\Tests;

use Hewerthomn\ErrorTracker\ErrorTrackerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ErrorTrackerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.debug', true);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('error-tracker.enabled', true);
        $app['config']->set('error-tracker.notifications.enabled', false);
        $app['config']->set('error-tracker.feedback.enabled', true);
        $app['config']->set('error-tracker.feedback.only_production', false);
        $app['config']->set('error-tracker.route.middleware', ['web']);

        $app['config']->set('error-tracker.capture.environments', [
            'testing',
            'local',
            'staging',
            'production',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('vendor:publish', [
            '--tag' => 'error-tracker-migrations',
            '--force' => true,
        ])->run();

        $this->artisan('migrate')->run();
    }
}
