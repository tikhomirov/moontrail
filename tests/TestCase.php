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

    /**
     * UI-адаптер пакета для тестового приложения.
     */
    protected function uiAdapter(): string
    {
        return 'moonshine';
    }

    /**
     * Нужно ли поднимать сервис-провайдер MoonShine (тесты без moonshine).
     */
    protected function usesMoonShine(): bool
    {
        return true;
    }

    protected function getPackageProviders($app): array
    {
        $providers = [];

        if ($this->usesMoonShine()) {
            $providers[] = MoonShineServiceProvider::class;
        }

        $providers[] = SpatieActivitylogServiceProvider::class;
        $providers[] = MoonTrailServiceProvider::class;

        if (class_exists(\Livewire\LivewireServiceProvider::class)) {
            $providers[] = \Livewire\LivewireServiceProvider::class;
        }

        return $providers;
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('activitylog.table_name', 'activity_log');
        $app['config']->set('activitylog.database_connection', 'testing');
        $app['config']->set('moontrail.ui_adapter', $this->uiAdapter());
        $app['config']->set('moontrail.activity_logger', 'spatie');
    }
}
