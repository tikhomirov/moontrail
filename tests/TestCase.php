<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\MoonTrail\MoonTrailServiceProvider;
use MoonShine\Laravel\Providers\MoonShineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider as SpatieActivitylogServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            MoonShineServiceProvider::class,
            SpatieActivitylogServiceProvider::class,
            MoonTrailServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('activitylog.table_name', 'activity_log');
        $app['config']->set('activitylog.database_connection', 'testing');
    }
}
