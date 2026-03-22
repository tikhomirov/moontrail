<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

final class ActivityRuntimeResolver
{
    public function resolve(?bool $spatieInstalled = null): ActivityRuntime
    {
        $configuredDriver = config('moontrail.activity_logger', 'auto');

        if (! is_string($configuredDriver) || $configuredDriver === '') {
            throw new RuntimeException('Unsupported moontrail.activity_logger driver: ' . get_debug_type($configuredDriver));
        }

        $resolvedDriver = $this->resolveDriver(configuredDriver: $configuredDriver, spatieInstalled: $spatieInstalled);
        $activityModel = $this->resolveActivityModel(configuredDriver: $configuredDriver, resolvedDriver: $resolvedDriver);

        return new ActivityRuntime(
            configuredDriver: $configuredDriver,
            resolvedDriver: $resolvedDriver,
            activityModel: $activityModel,
            supportsModelDetail: in_array($resolvedDriver, ['spatie', 'database'], true),
            supportsDynamicFilterOptions: in_array($resolvedDriver, ['spatie', 'database'], true),
        );
    }

    private function resolveDriver(string $configuredDriver, ?bool $spatieInstalled): string
    {
        return match ($configuredDriver) {
            'auto' => $this->isSpatieInstalled($spatieInstalled) ? 'spatie' : 'database',
            'spatie', 'database', 'none', 'custom' => $configuredDriver,
            default => throw new RuntimeException('Unsupported moontrail.activity_logger driver: ' . $configuredDriver),
        };
    }

    /**
     * @return class-string<Model>
     */
    private function resolveActivityModel(string $configuredDriver, string $resolvedDriver): string
    {
        if ($resolvedDriver === 'spatie') {
            return Activity::class;
        }

        if ($resolvedDriver === 'database' || $resolvedDriver === 'none') {
            return MoonTrailActivity::class;
        }

        $activityModel = config('moontrail.activity_model', MoonTrailActivity::class);

        if (! is_string($activityModel) || $activityModel === '') {
            throw new RuntimeException(
                sprintf(
                    'Invalid moontrail.activity_model for configured driver "%s": expected non-empty class-string, got %s',
                    $configuredDriver,
                    get_debug_type($activityModel),
                ),
            );
        }

        if (! class_exists($activityModel)) {
            throw new RuntimeException(
                sprintf(
                    'Invalid moontrail.activity_model for configured driver "%s": class "%s" does not exist',
                    $configuredDriver,
                    $activityModel,
                ),
            );
        }

        if (! is_subclass_of($activityModel, Model::class)) {
            throw new RuntimeException(
                sprintf(
                    'Invalid moontrail.activity_model for configured driver "%s": class "%s" must extend %s',
                    $configuredDriver,
                    $activityModel,
                    Model::class,
                ),
            );
        }

        /** @var class-string<Model> $activityModel */
        return $activityModel;
    }

    private function isSpatieInstalled(?bool $spatieInstalled): bool
    {
        if ($spatieInstalled !== null) {
            return $spatieInstalled;
        }

        return class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class);
    }
}
