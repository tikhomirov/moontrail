<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;

final readonly class ActivityRuntime
{
    /**
     * @param class-string<Model> $activityModel
     */
    public function __construct(
        public string $configuredDriver,
        public string $resolvedDriver,
        public string $activityModel,
        public bool $supportsModelDetail,
        public bool $supportsDynamicFilterOptions,
    ) {}
}
