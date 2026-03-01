<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use Throwable;

final class MoonTrailMenuItem
{
    private const FILTER_PARAM = 'subject_type';

    /**
     * Create a menu item/group for Activity Log.
     *
     * Usage in your MoonShineLayout::menu():
     *   MoonTrailMenuItem::make(),
     *
     * Returns null when menu.enabled = false (caller must filter nulls).
     * Returns MenuItem when show_children = false or no sub-items are available.
     * Returns MenuGroup when tracked models exist and show_children = true.
     *
     * Config keys (moontrail.menu):
     *   enabled        — false hides the menu entirely (returns null)
     *   label          — override default label
     *   show_all_item  — show the "All" (unfiltered) sub-item
     *   show_children  — false collapses to a single top-level MenuItem
     *   exclude_models — model classes to hide from sub-items
     */
    public static function make(?string $label = null): MenuGroup|MenuItem|null
    {
        if (! config('moontrail.menu.enabled', true)) {
            return null;
        }

        /** @var class-string<MoonTrailResource> $resourceClass */
        $resourceClass = config('moontrail.resource.class', MoonTrailResource::class);

        $icon = is_string(config('moontrail.resource.menu_icon'))
            ? config('moontrail.resource.menu_icon')
            : 'clock';

        $groupLabel = $label
            ?? (is_string(config('moontrail.menu.label')) ? config('moontrail.menu.label') : null)
            ?? (string) __('moontrail::ui.activity_log');

        $showChildren = (bool) config('moontrail.menu.show_children', true);
        $showAllItem = (bool) config('moontrail.menu.show_all_item', true);

        if (! $showChildren) {
            return MenuItem::make($resourceClass, $groupLabel)->icon($icon);
        }

        $trackedModels = self::resolveTrackedModels();
        $hasModels = $trackedModels !== [];

        if (! $hasModels) {
            if (! $showAllItem) {
                return null;
            }

            return MenuItem::make($resourceClass, $groupLabel)->icon($icon);
        }

        $items = [];

        if ($showAllItem) {
            $items[] = MenuItem::make(
                static fn (): string => self::resourceUrl($resourceClass),
                (string) __('moontrail::ui.menu_all'),
            )->whenActive(
                static fn (string $path): bool => self::isActiveForSubjectType($path, null),
            );
        }

        foreach ($trackedModels as $modelClass) {
            $modelLabel = class_basename($modelClass);
            $items[] = MenuItem::make(
                static fn (): string => self::resourceUrl($resourceClass, $modelClass),
                $modelLabel,
            )->whenActive(
                static fn (string $path): bool => self::isActiveForSubjectType($path, $modelClass),
            );
        }

        return MenuGroup::make($groupLabel, $items, $icon);
    }

    /**
     * @return list<class-string>
     */
    public static function resolveTrackedModels(): array
    {
        /** @var array<int, class-string> $exclude */
        $exclude = (array) config('moontrail.menu.exclude_models', []);

        $models = array_unique(array_filter(array_merge(
            (array) config('moontrail.auto_track_models', []),
            (array) config('moontrail.tracked_models', []),
        )));

        $models = array_filter(
            $models,
            static fn (mixed $model): bool => is_string($model) && class_exists($model) && ! in_array($model, $exclude, true),
        );

        return array_values($models);
    }

    /**
     * @param class-string<MoonTrailResource> $resourceClass
     * @param class-string|null $subjectType
     */
    private static function resourceUrl(string $resourceClass, ?string $subjectType = null): string
    {
        try {
            /** @var MoonTrailResource $resource */
            $resource = app($resourceClass);
            $url = $resource->getUrl();

            if ($subjectType !== null) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . self::FILTER_PARAM . '=' . urlencode($subjectType);
            }

            return $url;
        } catch (Throwable) {
            return '#';
        }
    }

    /**
     * @param class-string|null $subjectType
     */
    private static function isActiveForSubjectType(string $path, ?string $subjectType): bool
    {
        // MoonShine auto-generates kebab-case URI: "moon-trail-resource"
        if (! str_contains($path, 'moon-trail')) {
            return false;
        }

        $queryRaw = parse_url(request()->fullUrl(), PHP_URL_QUERY);
        $query = is_string($queryRaw) ? $queryRaw : '';
        parse_str($query, $params);

        $currentFilter = $params[self::FILTER_PARAM]
            ?? $params['filters'][self::FILTER_PARAM]
            ?? null;

        if ($subjectType === null) {
            return $currentFilter === null || $currentFilter === '';
        }

        return $currentFilter === $subjectType;
    }
}
