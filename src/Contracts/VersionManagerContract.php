<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

interface VersionManagerContract
{
    public function createVersion(Model $model, string $event, ?Activity $activity = null): ModelVersion;

    public function getVersion(Model $model, int $version): ?ModelVersion;

    /**
     * @return array<string, FieldChange>
     */
    public function diff(ModelVersion $from, ModelVersion $to): array;

    /**
     * @return array<string, FieldChange>
     */
    public function diffWithCurrent(ModelVersion $version, Model $model): array;

    public function enforceLimit(Model $model): void;
}
