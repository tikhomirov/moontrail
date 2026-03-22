<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;

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
        return $this->runtime->activityModel;
    }

    /**
     * @return class-string<Model>
     */
    public function resolveModelClass(): string
    {
        return $this->runtime->activityModel;
    }
}
