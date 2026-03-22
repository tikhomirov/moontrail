<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Logging;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;

use function class_basename;

final class DatabaseActivityLogger implements ActivityLoggerContract
{
    public function log(Model $model, string $event, array $data = []): ?int
    {
        $causer = $data['causer'] ?? null;
        $rawLogName = $data['log_name'] ?? 'default';
        $logName = is_string($rawLogName) && $rawLogName !== '' ? $rawLogName : 'default';

        $activity = MoonTrailActivity::query()->create([
            'log_name'     => $logName,
            'subject_type' => $model->getMorphClass(),
            'subject_id'   => $model->getKey(),
            'model_type'   => $model->getMorphClass(),
            'model_id'     => $model->getKey(),
            'event'        => $event,
            'properties'   => [
                'old'        => $data['old'] ?? [],
                'attributes' => $data['attributes'] ?? [],
            ],
            'causer_type' => $causer instanceof Model ? $causer->getMorphClass() : null,
            'causer_id'   => $causer instanceof Model ? $causer->getKey() : null,
            'description' => $data['description'] ?? (class_basename($model) . ' was ' . $event),
        ]);

        $key = $activity->getKey();

        return is_int($key) ? $key : null;
    }
}
