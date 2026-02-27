<?php

declare(strict_types=1);

namespace MoonShine\ActivityLog\Versioning;

use Illuminate\Database\Eloquent\Model;
use MoonShine\ActivityLog\Contracts\VersionManagerContract;
use MoonShine\ActivityLog\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

final class VersionManager implements VersionManagerContract
{
    public function createVersion(Model $model, string $event, ?Activity $activity = null): ModelVersion
    {
        // Placeholder
        return new ModelVersion();
    }
}
