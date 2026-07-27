<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

use function in_array;

final class ActivityRuntimeResolver
{
    public function resolve(?bool $spatieInstalled = null): ActivityRuntime
    {
        $configuredDriver = MoonTrailConfig::activityDriver();
        $resolvedDriver = $this->resolveDriver(configuredDriver: $configuredDriver, spatieInstalled: $spatieInstalled);
        $activityModel = $this->resolveActivityModel(resolvedDriver: $resolvedDriver);

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
            default => throw new RuntimeException('Unsupported moontrail.activity_logger: ' . $configuredDriver),
        };
    }

    /**
     * For built-in drivers the activity model is deterministic.
     * Custom mode defers to ActivityQueryContract::modelClass() at resolve time,
     * so we use MoonTrailActivity as a safe placeholder here.
     *
     * @return class-string<Model>
     */
    private function resolveActivityModel(string $resolvedDriver): string
    {
        return match ($resolvedDriver) {
            'spatie' => Activity::class,
            default  => MoonTrailActivity::class,
        };
    }

    private function isSpatieInstalled(?bool $spatieInstalled): bool
    {
        if ($spatieInstalled !== null) {
            return $spatieInstalled;
        }

        return class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class);
    }
}
