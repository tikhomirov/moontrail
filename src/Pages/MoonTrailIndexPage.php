<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Pages;

use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\MoonTrail\Enums\ActivityEvent;
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
     * Renders inline filters row similar to Filament.
     */
    private function renderInlineFilters(): string
    {
        $logNameOptions = $this->getDistinctOptions('log_name');
        $eventOptions = [];

        foreach (\MoonShine\MoonTrail\Enums\ActivityEvent::cases() as $case) {
            $eventOptions[$case->value] = $case->label();
        }

        $currentLogName = $this->resolveFilterValue('log_name') ?? '';
        $currentEvent = $this->resolveFilterValue('event') ?? '';
        $currentSearch = $this->resolveFilterValue('search') ?? $this->resolveFilterValue('query') ?? '';
        $currentDateFrom = $this->resolveFilterValue('date_from') ?? '';
        $currentDateUntil = $this->resolveFilterValue('date_until') ?? '';

        $labelLog = e((string) __('moontrail::ui.field_log'));
        $labelEvent = e((string) __('moontrail::ui.field_event'));
        $labelSearch = e((string) __('moontrail::ui.search'));
        $searchHint = e((string) __('moontrail::ui.search_hint'));
        $labelDateFrom = e((string) __('moontrail::ui.date_from'));
        $labelDateUntil = e((string) __('moontrail::ui.date_until'));

        $optionsHtml = fn (array $options, string $current): string => implode('', array_map(
            fn ($val, $lab): string => '<option value="' . e((string) $val) . '"' . ($current === (string) $val ? ' selected' : '') . '>' . e((string) $lab) . '</option>',
            array_keys($options),
            array_values($options),
        ));

        $anyLabel = e((string) __('moontrail::ui.any'));

        return <<<HTML
<div class="ms-al-inline-filters">
    <form method="GET" action="" class="ms-al-filter-grid">
        <!-- Search -->
        <div class="ms-al-filter-search">
            <label>{$labelSearch}</label>
            <div class="ms-al-search-input-wrap">
                <div class="ms-al-search-input-icon" aria-hidden="true">
                    <svg class="ms-al-search-input-icon-svg" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{$currentSearch}" placeholder="{$searchHint}" class="ms-al-search-input">
            </div>
        </div>

        <!-- Log Name -->
        <div>
            <label>{$labelLog}</label>
            <select name="log_name" onchange="this.form.submit()">
                <option value="">{$anyLabel}</option>
                {$optionsHtml($logNameOptions, $currentLogName)}
            </select>
        </div>

        <!-- Event -->
        <div>
            <label>{$labelEvent}</label>
            <select name="event" onchange="this.form.submit()">
                <option value="">{$anyLabel}</option>
                {$optionsHtml($eventOptions, $currentEvent)}
            </select>
        </div>

        <!-- Date From -->
        <div>
            <label>{$labelDateFrom}</label>
            <input type="date" name="date_from" value="{$currentDateFrom}" onchange="this.form.submit()">
        </div>

        <!-- Date Until -->
        <div>
            <label>{$labelDateUntil}</label>
            <input type="date" name="date_until" value="{$currentDateUntil}" onchange="this.form.submit()">
        </div>

        <div class="ms-al-filter-actions">
            <button type="submit" class="ms-al-btn-filter">
                Filter
            </button>
            <a href="?" class="ms-al-btn-reset" title="Reset">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        </div>
    </form>
</div>
HTML;
    }

    /**
     * @return array<string, string>
     */
    private function getDistinctOptions(string $column, bool $useClassBasename = false): array
    {
        /** @var list<string> $values */
        $values = Activity::query()
            ->distinct()
            ->whereNotNull($column)
            ->pluck($column)
            ->all();

        $options = [];

        foreach ($values as $value) {
            if ($value !== '') {
                $options[$value] = $useClassBasename ? class_basename($value) : $value;
            }
        }

        return $options;
    }

    /**
     * Returns an HTML block of active-filter chips, or '' when no filters are applied.
     * Each chip has a ✕ link that removes only that filter from the query string.
     */
    private function renderActiveFilterChips(): string
    {
        /** @var array<string, string> $filterKeys human-readable label per filter key */
        $filterKeys = [
            'log_name'     => (string) __('moontrail::ui.field_log'),
            'event'        => (string) __('moontrail::ui.field_event'),
            'subject_type' => (string) __('moontrail::ui.field_subject'),
            'subject_id'   => (string) __('moontrail::ui.field_subject_id'),
            'causer_type'  => (string) __('moontrail::ui.field_causer_type'),
            'causer_id'    => (string) __('moontrail::ui.field_causer_id'),
            'date_from'    => (string) __('moontrail::ui.date_from'),
            'date_until'   => (string) __('moontrail::ui.date_until'),
        ];

        $activeChips = [];

        foreach ($filterKeys as $key => $label) {
            $value = $this->resolveFilterValue($key);

            if ($value === null) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            // For the event filter show a human-readable label
            $displayValue = $key === 'event'
                ? (ActivityEvent::tryFrom($value)?->label() ?? $value)
                : $value;

            $removeUrl = e($this->buildRemoveFilterUrl($key));
            $labelText = e($label);
            $valueText = e($displayValue);
            $closeIcon = SvgIcons::check('w-3 h-3', 'flex-shrink-0'); // reuse × via CSS rotation trick? No — use ×

            $activeChips[] = <<<HTML
<span class="ms-al-filter-chip inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 text-xs font-medium rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
    <span class="opacity-60">{$labelText}:</span>
    <span class="font-semibold">{$valueText}</span>
    <a href="{$removeUrl}" class="ms-al-filter-chip-remove inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors duration-100 flex-shrink-0" title="Remove filter">
        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </a>
</span>
HTML;
        }

        if ($activeChips === []) {
            return '';
        }

        $chipsHtml = implode('', $activeChips);

        $clearAllLabel = e((string) __('moontrail::ui.filter_clear_all'));
        $clearAllUrl = e($this->buildClearAllFiltersUrl());

        return <<<HTML
<div class="ms-al-filter-chips flex flex-wrap items-center gap-2 mb-3">
    {$chipsHtml}
    <a href="{$clearAllUrl}" class="ms-al-filter-clear-all inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-150">
        {$clearAllLabel}
    </a>
</div>
HTML;
    }

    private function resolveFilterValue(string $key): ?string
    {
        $direct = request()->input($key);

        if (is_scalar($direct) && (string) $direct !== '') {
            return (string) $direct;
        }

        $nested = request()->input('filters.' . $key);

        if (is_scalar($nested) && (string) $nested !== '') {
            return (string) $nested;
        }

        return null;
    }

    private function buildRemoveFilterUrl(string $removeKey): string
    {
        $query = request()->except([$removeKey, 'filters.' . $removeKey, 'page']);

        // Also strip it from a nested filters array if present
        if (isset($query['filters'][$removeKey])) {
            unset($query['filters'][$removeKey]);
        }

        $qs = http_build_query($query);

        return request()->url() . ($qs !== '' ? '?' . $qs : '');
    }

    private function buildClearAllFiltersUrl(): string
    {
        $filterKeys = ['log_name', 'event', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'date_from', 'date_until'];

        $query = request()->except([...$filterKeys, 'filters', 'page']);

        $qs = http_build_query($query);

        return request()->url() . ($qs !== '' ? '?' . $qs : '');
    }

    private function renderKpiBlock(): string
    {
        $total = Activity::query()->count();
        $created = Activity::query()->where('event', 'created')->count();
        $updated = Activity::query()->where('event', 'updated')->count();
        $deleted = Activity::query()->where('event', 'deleted')->count();
        $other = $total - $created - $updated - $deleted;

        $labelTotal = e((string) __('moontrail::ui.kpi_total'));
        $labelCreated = e((string) __('moontrail::ui.kpi_created'));
        $labelUpdated = e((string) __('moontrail::ui.kpi_updated'));
        $labelDeleted = e((string) __('moontrail::ui.kpi_deleted'));
        $labelOther = e((string) __('moontrail::ui.kpi_other'));

        $baseUrl = e(request()->url());

        $cards = [
            $this->kpiCard($labelTotal, (string) $total, 'ms-al-kpi-card--total', $this->kpiIcon('total'), $this->buildKpiFilterUrl($baseUrl, null)),
            $this->kpiCard($labelCreated, (string) $created, 'ms-al-kpi-card--created', $this->kpiIcon('created'), $this->buildKpiFilterUrl($baseUrl, 'created')),
            $this->kpiCard($labelUpdated, (string) $updated, 'ms-al-kpi-card--updated', $this->kpiIcon('updated'), $this->buildKpiFilterUrl($baseUrl, 'updated')),
            $this->kpiCard($labelDeleted, (string) $deleted, 'ms-al-kpi-card--deleted', $this->kpiIcon('deleted'), $this->buildKpiFilterUrl($baseUrl, 'deleted')),
            $this->kpiCard($labelOther, (string) max(0, $other), 'ms-al-kpi-card--other', $this->kpiIcon('other'), null),
        ];

        $cardsHtml = implode('', $cards);

        return <<<HTML
<div class="ms-al-kpi-grid grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
    {$cardsHtml}
</div>
HTML;
    }

    private function buildKpiFilterUrl(string $baseUrl, ?string $event): string
    {
        $params = request()->except(['event', 'page']);

        if ($event !== null) {
            $params['event'] = $event;
        }

        $qs = http_build_query($params);

        return $baseUrl . ($qs !== '' ? '?' . $qs : '');
    }

    private function kpiCard(string $label, string $value, string $modifier, string $icon, ?string $href): string
    {
        $currentEvent = $this->resolveFilterValue('event') ?? '';
        $isActive = $href !== null && (
            ($modifier === 'ms-al-kpi-card--total' && $currentEvent === '')
            || ($modifier === 'ms-al-kpi-card--created' && $currentEvent === 'created')
            || ($modifier === 'ms-al-kpi-card--updated' && $currentEvent === 'updated')
            || ($modifier === 'ms-al-kpi-card--deleted' && $currentEvent === 'deleted')
        );

        $activeClass = $isActive ? ' ms-al-kpi-card--active ring-2 ring-offset-1 dark:ring-offset-gray-900' : '';
        $cursorClass = $href !== null ? ' cursor-pointer hover:border-gray-300 dark:hover:border-gray-600 transition-colors' : '';

        if ($href !== null) {
            $escapedHref = e($href);

            return <<<HTML
<a href="{$escapedHref}" class="ms-al-kpi-card {$modifier}{$activeClass}{$cursorClass} flex items-center gap-3 px-4 py-3 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/80 shadow-sm no-underline">
    <div class="ms-al-kpi-icon flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center">
        {$icon}
    </div>
    <div class="min-w-0">
        <div class="ms-al-kpi-value text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{$value}</div>
        <div class="ms-al-kpi-label text-xs text-gray-500 dark:text-gray-400 truncate">{$label}</div>
    </div>
</a>
HTML;
        }

        return <<<HTML
<div class="ms-al-kpi-card {$modifier} flex items-center gap-3 px-4 py-3 rounded-xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/80 shadow-sm">
    <div class="ms-al-kpi-icon flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center">
        {$icon}
    </div>
    <div class="min-w-0">
        <div class="ms-al-kpi-value text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{$value}</div>
        <div class="ms-al-kpi-label text-xs text-gray-500 dark:text-gray-400 truncate">{$label}</div>
    </div>
</div>
HTML;
    }

    private function kpiIcon(string $type): string
    {
        return match ($type) {
            'total'   => SvgIcons::document('w-5 h-5', 'text-gray-500 dark:text-gray-400'),
            'created' => SvgIcons::created('w-5 h-5', 'text-emerald-600 dark:text-emerald-400'),
            'updated' => SvgIcons::updated('w-5 h-5', 'text-blue-600 dark:text-blue-400'),
            'deleted' => SvgIcons::deleted('w-5 h-5', 'text-red-600 dark:text-red-400'),
            default   => SvgIcons::dotsHorizontal('w-5 h-5', 'text-orange-500 dark:text-orange-400'),
        };
    }
}
