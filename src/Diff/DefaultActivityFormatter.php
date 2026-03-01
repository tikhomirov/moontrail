<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;

final class DefaultActivityFormatter implements ActivityFormatterContract
{
    public function format(Activity $activity): array
    {
        $event = ActivityEvent::tryFrom((string) $activity->event);

        if ($event instanceof ActivityEvent) {
            return [
                'description' => $event->label(),
                'icon'        => $event->icon(),
                'color'       => $event->color(),
            ];
        }

        return [
            'description' => (string) $activity->description,
            'icon'        => 'info',
            'color'       => 'gray',
        ];
    }
}
