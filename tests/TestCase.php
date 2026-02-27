<?php

namespace MoonShine\ActivityLog\Tests;

use MoonShine\ActivityLog\ActivityLogServiceProvider;
use MoonShine\Laravel\Providers\MoonShineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider as SpatieActivitylogServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MoonShineServiceProvider::class,
            SpatieActivitylogServiceProvider::class,
            ActivityLogServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // Load migrations from the package
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load migrations from Spatie (it might be needed)
        // Usually Spatie migrations are published, but orchestra can load them if we find the path
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
    }
}
