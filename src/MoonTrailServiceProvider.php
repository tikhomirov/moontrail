<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\MoonTrail\Activity\DatabaseActivityQuery;
use MoonShine\MoonTrail\Activity\NullActivityQuery;
use MoonShine\MoonTrail\Activity\SpatieActivityQuery;
use MoonShine\MoonTrail\Console\Commands\InstallMoonTrailCommand;
use MoonShine\MoonTrail\Console\Commands\PruneMoonTrailCommand;
use MoonShine\MoonTrail\Contracts\ActivityFilterOptionsContract;
use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryUiContract;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityQueryContract;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Diff\DefaultActivityFormatter;
use MoonShine\MoonTrail\Diff\HtmlDiffRenderer;
use MoonShine\MoonTrail\Logging\DatabaseActivityLogger;
use MoonShine\MoonTrail\Logging\NullActivityLogger;
use MoonShine\MoonTrail\Logging\SpatieActivityLogger;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Support\ActivityFilterOptionsProvider;
use MoonShine\MoonTrail\Support\ActivityModelResolver;
use MoonShine\MoonTrail\Support\ActivityRecordFactory;
use MoonShine\MoonTrail\Support\ActivityRuntime;
use MoonShine\MoonTrail\Support\ActivityRuntimeResolver;
use MoonShine\MoonTrail\Support\MoonTrailConfig;
use MoonShine\MoonTrail\Support\MoonTrailLogger;
use MoonShine\MoonTrail\Versioning\RollbackAuthorizationResolver;
use MoonShine\MoonTrail\Versioning\RollbackService;
use MoonShine\MoonTrail\Versioning\VersionManager;
use RuntimeException;
use Throwable;

final class MoonTrailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moontrail.php', 'moontrail');

        $this->app->bind(DiffRendererContract::class, HtmlDiffRenderer::class);
        $this->app->bind(VersionManagerContract::class, VersionManager::class);
        $this->app->bind(RollbackStrategyContract::class, RollbackService::class);
        $this->app->bind(ActivityFormatterContract::class, DefaultActivityFormatter::class);
        $this->app->bind(ActivityFilterOptionsContract::class, ActivityFilterOptionsProvider::class);
        $this->app->singleton(MoonTrailLogger::class);
        $this->app->singleton(RollbackAuthorizationResolver::class);
        $this->app->bind(ActivityModelResolver::class);
        $this->registerActivityRuntime();
        $this->app->singleton(ActivityRecordFactory::class);

        $this->registerActivityLogger();
        $this->registerActivityQuery();
        $this->registerModelBackedActivityQuery();
        $this->registerActivityQueryUi();
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
        $this->registerModelVersionActivityResolver();
        $this->validateCustomModeBindings();
        $this->logRuntimeResolution();

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

    private function registerActivityLogger(): void
    {
        $this->app->singleton(ActivityLoggerContract::class, function (): ActivityLoggerContract {
            $runtime = $this->app->make(ActivityRuntime::class);

            if ($runtime->resolvedDriver === 'custom') {
                throw $this->customModeBindingException(
                    missingBinding: ActivityLoggerContract::class,
                    runtime: $runtime,
                );
            }

            return match ($runtime->resolvedDriver) {
                'spatie'   => new SpatieActivityLogger,
                'database' => new DatabaseActivityLogger,
                'none'     => new NullActivityLogger,
                default    => throw new RuntimeException('Unsupported moontrail.activity.driver: ' . $runtime->resolvedDriver),
            };
        });
    }

    private function registerActivityQuery(): void
    {
        $this->app->singleton(ActivityQueryContract::class, function (): ActivityQueryContract {
            $runtime = $this->app->make(ActivityRuntime::class);

            if ($runtime->resolvedDriver === 'custom') {
                throw $this->customModeBindingException(
                    missingBinding: ActivityQueryContract::class,
                    runtime: $runtime,
                );
            }

            return match ($runtime->resolvedDriver) {
                'spatie'   => new SpatieActivityQuery,
                'database' => new DatabaseActivityQuery,
                'none'     => new NullActivityQuery,
                default    => throw new RuntimeException('Unsupported moontrail.activity.driver: ' . $runtime->resolvedDriver),
            };
        });
    }

    private function registerActivityRuntime(): void
    {
        $this->app->singleton(ActivityRuntimeResolver::class);

        $this->app->bind(ActivityRuntime::class, fn (): ActivityRuntime => $this->app->make(ActivityRuntimeResolver::class)->resolve());
    }

    private function registerActivityQueryUi(): void
    {
        $this->app->singleton(ActivityQueryUiContract::class, function (): \MoonShine\MoonTrail\Contracts\ActivityQueryUiContract {
            $query = $this->app->make(ActivityQueryContract::class);

            if (! $query instanceof ActivityQueryUiContract) {
                throw new RuntimeException(
                    'ActivityQueryUiContract is deprecated compatibility layer; ActivityQueryContract binding must implement it only for legacy custom integrations.',
                );
            }

            return $query;
        });
    }

    private function registerModelBackedActivityQuery(): void
    {
        $this->app->singleton(ModelBackedActivityQueryContract::class, function (): \MoonShine\MoonTrail\Contracts\ModelBackedActivityQueryContract {
            $query = $this->app->make(ActivityQueryContract::class);

            if (! $query instanceof ModelBackedActivityQueryContract) {
                throw new RuntimeException(
                    'ModelBackedActivityQueryContract is deprecated compatibility layer; ActivityQueryContract binding must implement it only for legacy custom integrations.',
                );
            }

            return $query;
        });
    }

    private function validateCustomModeBindings(): void
    {
        $runtime = $this->app->make(ActivityRuntime::class);

        if ($runtime->resolvedDriver !== 'custom') {
            return;
        }

        $this->assertCustomBinding(ActivityLoggerContract::class, $runtime);
        $this->assertCustomBinding(ActivityQueryContract::class, $runtime);
    }

    private function assertCustomBinding(string $binding, ActivityRuntime $runtime): void
    {
        try {
            $this->app->make($binding);
        } catch (Throwable $exception) {
            throw $this->customModeBindingException(
                missingBinding: $binding,
                runtime: $runtime,
                previous: $exception,
            );
        }
    }

    private function customModeBindingException(
        string $missingBinding,
        ActivityRuntime $runtime,
        ?Throwable $previous = null,
    ): RuntimeException {
        $requiredBindings = [
            ActivityLoggerContract::class,
            ActivityQueryContract::class,
        ];

        return new RuntimeException(
            sprintf(
                'MoonTrail custom mode binding error: configured_driver=%s; missing_binding=%s; required_bindings=%s',
                $runtime->configuredDriver,
                $missingBinding,
                implode(', ', $requiredBindings),
            ),
            previous: $previous,
        );
    }

    private function registerResource(): void
    {
        if (! MoonTrailConfig::resourceRegister()) {
            return;
        }

        /** @var class-string<ResourceContract> $resourceClass */
        $resourceClass = MoonTrailConfig::resourceClass();

        $this->app->afterResolving(CoreContract::class, static function (CoreContract $core) use ($resourceClass): void {
            $core->resources([$resourceClass]);
        });
    }

    private function registerAutoTracking(): void
    {
        /** @var array<int, class-string> $models */
        $models = array_unique(array_merge(
            MoonTrailConfig::autoTrackModels(),
            MoonTrailConfig::menuModels(),
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

                if (! is_subclass_of($modelClass, Model::class)) {
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

    private function registerModelVersionActivityResolver(): void
    {
        ModelVersion::resolveActivityModelUsing(function (): string {
            /** @var class-string<Model> $activityModelClass */
            $activityModelClass = $this->app->make(ActivityModelResolver::class)->resolveModelClass();

            return $activityModelClass;
        });
    }

    private function warnAboutTailwindDependency(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        if (! MoonTrailConfig::uiWarnIfTailwindMissing()) {
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

    private function logRuntimeResolution(): void
    {
        $runtime = $this->app->make(ActivityRuntime::class);
        $event = $runtime->configuredDriver === 'auto'
            ? sprintf('auto resolved to "%s" driver', $runtime->resolvedDriver)
            : 'runtime_resolved';

        $this->app->make(MoonTrailLogger::class)->info($event, [
            'configured_driver' => $runtime->configuredDriver,
            'resolved_driver'   => $runtime->resolvedDriver,
            'activity_model'    => $runtime->activityModel,
            'resolution_mode'   => $runtime->configuredDriver === 'auto' ? 'auto_detected' : 'explicit',
        ]);
    }
}
