<?php

declare(strict_types=1);

namespace MoonShine\ActivityLog\Contracts;

use Illuminate\Database\Eloquent\Model;
use MoonShine\ActivityLog\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

interface VersionManagerContract
{
    public function createVersion(Model $model, string $event, ?Activity $activity = null): ModelVersion;
}
