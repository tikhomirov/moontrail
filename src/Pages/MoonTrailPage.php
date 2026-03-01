<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Preview;
use Spatie\Activitylog\Models\Activity;

final class MoonTrailPage extends Page
{
    public function getTitle(): string
    {
        return (string) __('moontrail::ui.activity_log');
    }

    protected function components(): iterable
    {
        $total = Activity::query()->count();

        return [
            Box::make([
                Preview::make('', formatted: static fn (): string => '<p class="text-sm text-gray-500 dark:text-gray-400 mb-4">'
                    . __('moontrail::ui.activity_log')
                    . ' — ' . $total . ' records</p>'),
            ]),
        ];
    }
}
