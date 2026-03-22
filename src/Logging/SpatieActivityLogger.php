<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Logging;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use Spatie\Activitylog\Models\Activity;

use function class_basename;

final class SpatieActivityLogger implements ActivityLoggerContract
{
    public function log(Model $model, string $event, array $data = []): ?int
    {
        if (! function_exists('activity')) {
            return null;
        }

        $builder = activity()
            ->performedOn($model)
            ->event($event)
            ->withProperties([
                'old'        => $data['old'] ?? [],
                'attributes' => $data['attributes'] ?? [],
            ]);

        $causer = $data['causer'] ?? null;

        if ($causer instanceof Model) {
            $builder->causedBy($causer);
        }

        $rawDescription = $data['description'] ?? null;
        $description = is_string($rawDescription) ? $rawDescription : (class_basename($model) . ' was ' . $event);

        /** @var Activity $activity */
        $activity = $builder->log($description);

        $key = $activity->getKey();

        return is_int($key) ? $key : null;
    }
}
