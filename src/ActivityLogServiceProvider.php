<?php

declare(strict_types=1);

namespace MoonShine\ActivityLog;

use Illuminate\Support\ServiceProvider;
use MoonShine\ActivityLog\Contracts\DiffRendererContract;
use MoonShine\ActivityLog\Contracts\VersionManagerContract;
use MoonShine\ActivityLog\Contracts\RollbackStrategyContract;
use MoonShine\ActivityLog\Diff\HtmlDiffRenderer;
use MoonShine\ActivityLog\Versioning\VersionManager;
use MoonShine\ActivityLog\Versioning\RollbackService;

final class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/activity-log.php', 'moonshine-activity-log');

        $this->app->bind(DiffRendererContract::class, HtmlDiffRenderer::class);
        $this->app->bind(VersionManagerContract::class, VersionManager::class);
        $this->app->bind(RollbackStrategyContract::class, RollbackService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'moonshine-activity-log');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moonshine-activity-log');
        $this->loadRoutesFrom(__DIR__ . '/../routes/activity-log.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/activity-log.php' => config_path('moonshine-activity-log.php'),
            ], 'moonshine-activity-log-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/moonshine-activity-log'),
            ], 'moonshine-activity-log-views');

            $this->publishes([
                __DIR__ . '/../lang' => lang_path('vendor/moonshine-activity-log'),
            ], 'moonshine-activity-log-lang');
        }
    }
}
