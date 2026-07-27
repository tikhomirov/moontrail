<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Traits;

use MoonShine\MoonTrail\Components\ActivityTimeline;
use MoonShine\MoonTrail\Support\MoonTrailConfig;
use MoonShine\Support\Enums\Layer;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;

trait WithMoonTrailTab
{
    protected function activityTabLabel(): string
    {
        return __('moontrail::ui.history');
    }

    protected function activityTabLimit(): int
    {
        return MoonTrailConfig::uiPerPage();
    }

    protected function activityTabShowRollback(): bool
    {
        return true;
    }

    protected function bootWithMoonTrailTab(): void
    {
        $this->getDetailPage()
            ->pushToLayer(
                layer: Layer::BOTTOM,
                component: Tabs::make([
                    Tab::make($this->activityTabLabel(), [
                        ActivityTimeline::make(
                            label: $this->activityTabLabel(),
                            resource: $this,
                        )
                            ->limit($this->activityTabLimit())
                            ->when(
                                ! $this->activityTabShowRollback(),
                                fn (ActivityTimeline $timeline): ActivityTimeline => $timeline->withoutRollback(),
                            ),
                    ]),
                ]),
            );
    }
}
