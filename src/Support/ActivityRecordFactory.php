<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Activity\DatabaseActivityAdapter;
use MoonShine\MoonTrail\Activity\SpatieActivityAdapter;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use RuntimeException;

final class ActivityRecordFactory
{
    public function fromModel(Model $activity): ActivityRecordContract
    {
        if ($activity instanceof ModelBackedActivityRecordContract) {
            return $activity;
        }

        if ($activity instanceof ActivityRecordContract) {
            return $activity;
        }

        if ($activity instanceof MoonTrailActivity) {
            return new DatabaseActivityAdapter($activity);
        }

        if (class_exists(\Spatie\Activitylog\Models\Activity::class) && $activity instanceof \Spatie\Activitylog\Models\Activity) {
            return new SpatieActivityAdapter($activity);
        }

        throw new RuntimeException('Unsupported activity model: ' . $activity::class);
    }
}
