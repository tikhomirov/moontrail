<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Enums\ActivityEvent;

final class DefaultActivityFormatter implements ActivityFormatterContract
{
    public function format(ActivityRecordContract $activity): array
    {
        $event = ActivityEvent::tryFrom($activity->getEvent());

        if ($event instanceof ActivityEvent) {
            return [
                'description' => $event->label(),
                'icon'        => $event->icon(),
                'color'       => $event->color(),
            ];
        }

        return [
            'description' => $activity->getDescription() ?? '',
            'icon'        => 'info',
            'color'       => 'gray',
        ];
    }
}
