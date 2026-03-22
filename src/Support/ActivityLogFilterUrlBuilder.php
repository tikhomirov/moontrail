<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Http\Request;

/**
 * Builds filter-related URLs for activity log filtering UI.
 *
 * Encapsulates the logic of constructing URLs with removed/added/cleared filters,
 * supporting both direct and nested filter parameter formats.
 */
final readonly class ActivityLogFilterUrlBuilder
{
    public function __construct(
        private string $baseUrl,
        private Request $request,
    ) {}

    /**
     * Build a URL that removes a specific filter.
     */
    public function removeFilterUrl(string $filterKey): string
    {
        $query = $this->request->except([$filterKey, 'filters.' . $filterKey, 'page']);

        if (isset($query['filters'][$filterKey])) {
            unset($query['filters'][$filterKey]);
        }

        $qs = http_build_query($query);

        return $this->baseUrl . ($qs !== '' ? '?' . $qs : '');
    }

    /**
     * Build a URL that clears all filters.
     *
     * @param list<string> $filterKeys
     */
    public function clearAllFiltersUrl(array $filterKeys): string
    {
        $query = $this->request->except([...$filterKeys, 'filters', 'page']);

        $qs = http_build_query($query);

        return $this->baseUrl . ($qs !== '' ? '?' . $qs : '');
    }

    /**
     * Build a URL that sets/removes an event filter.
     */
    public function eventFilterUrl(?string $event): string
    {
        $params = $this->request->except(['event', 'filters.event', 'page']);

        if (isset($params['filters']['event'])) {
            unset($params['filters']['event']);
        }

        if ($event !== null) {
            $params['event'] = $event;
        }

        $qs = http_build_query($params);

        return $this->baseUrl . ($qs !== '' ? '?' . $qs : '');
    }
}
