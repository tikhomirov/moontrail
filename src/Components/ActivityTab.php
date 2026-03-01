<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Components;

use MoonShine\UI\Components\MoonShineComponent;

final class ActivityTab extends MoonShineComponent
{
    protected string $view = 'moontrail::components.activity-tab';

    public function __construct(
        private readonly object $resource,
        private readonly int $limit = 20,
        private readonly bool $showRollback = true,
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        $timeline = ActivityTimeline::make(
            label: __('moontrail::ui.history'),
            resource: $this->resource,
        )->limit($this->limit);

        if (! $this->showRollback) {
            $timeline->withoutRollback();
        }

        return [
            'timeline' => $timeline,
        ];
    }
}
