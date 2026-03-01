<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\MoonTrail\Console\Commands\InstallMoonTrailCommand;
use MoonShine\MoonTrail\Console\Commands\PruneMoonTrailCommand;
use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Diff\DefaultActivityFormatter;
use MoonShine\MoonTrail\Diff\HtmlDiffRenderer;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Versioning\RollbackAuthorizationResolver;
use MoonShine\MoonTrail\Versioning\RollbackService;
use MoonShine\MoonTrail\Versioning\VersionManager;

final class MoonTrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moontrail.php', 'moontrail');

        $this->app->bind(DiffRendererContract::class, HtmlDiffRenderer::class);
        $this->app->bind(VersionManagerContract::class, VersionManager::class);
        $this->app->bind(RollbackStrategyContract::class, RollbackService::class);
        $this->app->bind(ActivityFormatterContract::class, DefaultActivityFormatter::class);
        $this->app->singleton(RollbackAuthorizationResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'moontrail');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moontrail');
        $this->loadRoutesFrom(__DIR__ . '/../routes/moontrail.php');

        $this->warnAboutTailwindDependency();

        $this->registerResource();
        $this->registerAutoTracking();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallMoonTrailCommand::class,
                PruneMoonTrailCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/moontrail.php' => config_path('moontrail.php'),
            ], 'moontrail-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/moontrail'),
            ], 'moontrail-views');

            $this->publishes([
                __DIR__ . '/../lang' => lang_path('vendor/moontrail'),
            ], 'moontrail-lang');

            $this->publishes([
                __DIR__ . '/../resources/assets' => public_path('vendor/moontrail'),
            ], 'moontrail-assets');
        }
    }

    private function registerResource(): void
    {
        if (! config('moontrail.resource.in_menu', true)) {
            return;
        }

        /** @var class-string<\MoonShine\Contracts\Core\ResourceContract> $resourceClass */
        $resourceClass = config('moontrail.resource.class', MoonTrailResource::class);

        $this->app->afterResolving(CoreContract::class, static function (CoreContract $core) use ($resourceClass): void {
            $core->resources([$resourceClass]);
        });
    }

    private function registerAutoTracking(): void
    {
        /** @var array<int, class-string> $models */
        $models = array_unique(array_merge(
            (array) config('moontrail.auto_track_models', []),
            (array) config('moontrail.tracked_models', []),
        ));

        if ($models === []) {
            return;
        }

        $this->app->booted(function () use ($models): void {
            $observer = $this->app->make(MoonTrailObserver::class);

            foreach ($models as $modelClass) {
                if (! class_exists($modelClass)) {
                    continue;
                }

                if (! is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
                    continue;
                }

                // Skip models that already have HasMoonTrail — the trait boot
                // attaches the observer itself, so we must not double-attach.
                if (in_array(Traits\HasMoonTrail::class, class_uses_recursive($modelClass), true)) {
                    continue;
                }

                $modelClass::observe($observer);
            }
        });
    }

    private function warnAboutTailwindDependency(): void
    {
        if (! config('moontrail.ui.warn_if_tailwind_missing', true)) {
            return;
        }

        $requiredPaths = [
            'vendor/tikhomirov/moontrail/resources/**/*.blade.php',
            'vendor/tikhomirov/moontrail/src/**/*.php',
        ];

        $tailwindConfigFiles = [
            base_path('tailwind.config.js'),
            base_path('tailwind.config.cjs'),
            base_path('tailwind.config.mjs'),
            base_path('tailwind.config.ts'),
        ];

        $tailwindConfigPath = null;

        foreach ($tailwindConfigFiles as $path) {
            if (file_exists($path)) {
                $tailwindConfigPath = $path;
                break;
            }
        }

        if ($tailwindConfigPath === null) {
            Log::warning('MoonShine Logs UI requires Tailwind CSS in the host app. Tailwind config file was not found.', [
                'expected_content_paths' => $requiredPaths,
                'disable_warning_config' => 'moontrail.ui.warn_if_tailwind_missing',
            ]);

            return;
        }

        $configContent = file_get_contents($tailwindConfigPath);

        if (! is_string($configContent)) {
            return;
        }

        $hasViewsPath = str_contains($configContent, 'vendor/tikhomirov/moontrail/resources');
        $hasSourcePath = str_contains($configContent, 'vendor/tikhomirov/moontrail/src');

        if ($hasViewsPath && $hasSourcePath) {
            return;
        }

        $missing = [];

        if (! $hasViewsPath) {
            $missing[] = $requiredPaths[0];
        }

        if (! $hasSourcePath) {
            $missing[] = $requiredPaths[1];
        }

        Log::warning('MoonShine Logs UI may render incorrectly. Add package paths to Tailwind content in host app.', [
            'tailwind_config'        => $tailwindConfigPath,
            'missing_content_paths'  => $missing,
            'disable_warning_config' => 'moontrail.ui.warn_if_tailwind_missing',
        ]);
    }
}
