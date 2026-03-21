<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Pages;

use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;
use MoonShine\MoonTrail\Support\ActivityLogFilterUrlBuilder;
use MoonShine\MoonTrail\Support\SvgIcons;
use MoonShine\UI\Components\FlexibleRender;
use Spatie\Activitylog\Models\Activity;

/**
 * Index page for the Activity Log resource.
 *
 * Extends the default IndexPage to prepend a KPI stats block above the
 * standard table — showing event counts (total / created / updated /
 * deleted / other) for the last 30 days.
 */
final class MoonTrailIndexPage extends IndexPage
{
    /**
     * @return list<ComponentContract>
     */
    protected function topLayer(): array
    {
        $assetUrl = e($this->resolveStylesheetAssetUrl());

        $kpiHtml = $this->renderKpiBlock();
        $inlineFiltersHtml = $this->renderInlineFilters();
        $chipsHtml = $this->renderActiveFilterChips();

        $components = [
            FlexibleRender::make("<link rel=\"stylesheet\" href=\"{$assetUrl}\">"),
            FlexibleRender::make($kpiHtml),
            FlexibleRender::make($inlineFiltersHtml),
        ];

        if ($chipsHtml !== '') {
            $components[] = FlexibleRender::make($chipsHtml);
        }

        return [
            ...$components,
            ...parent::topLayer(),
        ];
    }

    private function resolveStylesheetAssetUrl(): string
    {
        return (string) asset('vendor/moontrail/moontrail.css');
    }

    /**
     * Renders inline filters row using a Blade partial.
     */
    private function renderInlineFilters(): string
    {
        $filterData = ActivityLogFilterData::fromRequest();
        $filterOptions = new ActivityLogFilterOptions;

        $eventOptions = [];

        foreach (ActivityEvent::cases() as $case) {
            $eventOptions[$case->value] = $case->label();
        }

        return view('moontrail::pages.index-filters', [
            'logNameOptions'   => $filterOptions->logNames(),
            'eventOptions'     => $eventOptions,
            'currentLogName'   => $filterData->logName ?? '',
            'currentEvent'     => $filterData->event ?? '',
            'currentSearch'    => $filterData->search ?? '',
            'currentDateFrom'  => $filterData->dateFrom ?? '',
            'currentDateUntil' => $filterData->dateUntil ?? '',
        ])->render();
    }

    /**
     * Returns an HTML block of active-filter chips, or '' when no filters are applied.
     */
    private function renderActiveFilterChips(): string
    {
        $filterData = ActivityLogFilterData::fromRequest();
        $baseUrl = request()->url();
        $urlBuilder = new ActivityLogFilterUrlBuilder($baseUrl);

        /** @var array<int, array{label: string, value: string, removeUrl: string}> $chips */
        $chips = [];

        foreach ($filterData->activeFilterChips() as $chipData) {
            $chips[] = [
                'label'     => $chipData['label'],
                'value'     => $chipData['value'],
                'removeUrl' => $urlBuilder->removeFilterUrl($chipData['requestKey']),
            ];
        }

        if ($chips === []) {
            return '';
        }

        return view('moontrail::pages.filter-chips', [
            'chips'       => $chips,
            'clearAllUrl' => $urlBuilder->clearAllFiltersUrl(ActivityLogFilterData::filterRequestKeys()),
        ])->render();
    }

    private function renderKpiBlock(): string
    {
        $filterData = ActivityLogFilterData::fromRequest();
        $baseUrl = request()->url();
        $urlBuilder = new ActivityLogFilterUrlBuilder($baseUrl);

        $total = Activity::query()->count();
        $created = Activity::query()->where('event', 'created')->count();
        $updated = Activity::query()->where('event', 'updated')->count();
        $deleted = Activity::query()->where('event', 'deleted')->count();
        $other = max(0, $total - $created - $updated - $deleted);

        $currentEvent = $filterData->event ?? '';

        /** @var array<int, array{label: string, value: string, modifier: string, icon: string, href: ?string, isActive: bool}> $cards */
        $cards = [
            [
                'label'    => (string) __('moontrail::ui.kpi_total'),
                'value'    => (string) $total,
                'modifier' => 'ms-al-kpi-card--total',
                'icon'     => SvgIcons::document('w-5 h-5', 'text-gray-500 dark:text-gray-400'),
                'href'     => $urlBuilder->eventFilterUrl(null),
                'isActive' => $currentEvent === '',
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_created'),
                'value'    => (string) $created,
                'modifier' => 'ms-al-kpi-card--created',
                'icon'     => SvgIcons::created('w-5 h-5', 'text-emerald-600 dark:text-emerald-400'),
                'href'     => $urlBuilder->eventFilterUrl('created'),
                'isActive' => $currentEvent === 'created',
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_updated'),
                'value'    => (string) $updated,
                'modifier' => 'ms-al-kpi-card--updated',
                'icon'     => SvgIcons::updated('w-5 h-5', 'text-blue-600 dark:text-blue-400'),
                'href'     => $urlBuilder->eventFilterUrl('updated'),
                'isActive' => $currentEvent === 'updated',
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_deleted'),
                'value'    => (string) $deleted,
                'modifier' => 'ms-al-kpi-card--deleted',
                'icon'     => SvgIcons::deleted('w-5 h-5', 'text-red-600 dark:text-red-400'),
                'href'     => $urlBuilder->eventFilterUrl('deleted'),
                'isActive' => $currentEvent === 'deleted',
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_other'),
                'value'    => (string) $other,
                'modifier' => 'ms-al-kpi-card--other',
                'icon'     => SvgIcons::dotsHorizontal('w-5 h-5', 'text-orange-500 dark:text-orange-400'),
                'href'     => null,
                'isActive' => false,
            ],
        ];

        return view('moontrail::pages.index-kpi', ['cards' => $cards])->render();
    }
}
