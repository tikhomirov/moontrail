<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;

final readonly class ActivityModelResolver
{
    public function __construct(
        private ActivityRuntime $runtime,
    ) {}

    /**
     * @return class-string<Model>
     */
    public function resolve(): string
    {
        // For custom driver, defer to ActivityQueryContract if available
        if ($this->runtime->configuredDriver === 'custom' && app()->bound(ActivityQueryContract::class)) {
            $model = app(ActivityQueryContract::class)->modelClass();

            /** @phpstan-ignore booleanAnd.alwaysTrue, notIdentical.alwaysTrue, function.alreadyNarrowedType */
            if (is_string($model) && $model !== '') {
                return $model;
            }
        }

        return $this->runtime->activityModel;
    }

    /**
     * @return class-string<Model>
     */
    public function resolveModelClass(): string
    {
        return $this->resolve();
    }
}
