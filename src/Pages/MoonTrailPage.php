<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Pages;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Preview;

final class MoonTrailPage extends Page
{
    /**
     * @param ActivityQueryContract<Model> $activityQuery
     */
    public function __construct(
        CoreContract $core,
        private readonly ActivityQueryContract $activityQuery,
    ) {
        parent::__construct($core);
    }

    public function getTitle(): string
    {
        return (string) __('moontrail::ui.activity_log');
    }

    protected function components(): iterable
    {
        $total = $this->activityQuery->stats()['total'];

        return [
            Box::make([
                Preview::make('', formatted: static fn (): string => '<p class="text-sm text-gray-500 dark:text-gray-400 mb-4">'
                    . __('moontrail::ui.activity_log')
                    . ' — ' . $total . ' records</p>'),
            ]),
        ];
    }
}
