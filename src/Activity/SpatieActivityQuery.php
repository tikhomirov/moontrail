<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Activity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogQuery;
use Spatie\Activitylog\Models\Activity;

/**
 * @implements ActivityQueryContract<Activity>
 */
final class SpatieActivityQuery implements ActivityQueryContract
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $filterData = ActivityLogFilterData::fromArray($filters);
        $rawPerPage = config('moontrail.ui.per_page');
        $perPage = is_numeric($rawPerPage) ? (int) $rawPerPage : 20;

        $paginator = (new ActivityLogQuery)->apply(Activity::query(), $filterData)
            ->with(['subject', 'causer'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(
                static fn (Model $model): Model => $model,
            ),
        );

        return $paginator;
    }

    public function find(int|string $id): ?ActivityRecordContract
    {
        $activity = Activity::query()->with(['subject', 'causer'])->find($id);

        return $activity instanceof Activity ? new SpatieActivityAdapter($activity) : null;
    }

    public function stats(): array
    {
        /** @var array<string, int> $eventCounts */
        $eventCounts = Activity::query()
            ->selectRaw('event, count(*) as cnt')
            ->groupBy('event')
            ->pluck('cnt', 'event')
            ->all();

        $created = (int) ($eventCounts['created'] ?? 0);
        $updated = (int) ($eventCounts['updated'] ?? 0);
        $deleted = (int) ($eventCounts['deleted'] ?? 0);
        $total = array_sum($eventCounts);

        return [
            'total'   => $total,
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'other'   => max(0, $total - $created - $updated - $deleted),
        ];
    }

    public function modelClass(): string
    {
        return Activity::class;
    }

    public function distinctValues(string $column): array
    {
        if (! in_array($column, ['log_name', 'event', 'subject_type', 'causer_type'], true)) {
            return [];
        }

        $this->warnDistinctValuesIfLarge(Activity::query()->getModel(), $column);

        /** @var list<string> $values */
        $values = Activity::query()
            ->distinct()
            ->whereNotNull($column)
            ->pluck($column)
            ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        return $values;
    }

    private function warnDistinctValuesIfLarge(Model $model, string $column): void
    {
        $warnOnExpensive = config('moontrail.filter_options.warn_on_expensive_distinct_values');

        if (! is_bool($warnOnExpensive)) {
            $warnOnExpensive = (bool) config('moontrail.ui.warn_on_expensive_distinct_values', true);
        }

        if (! $warnOnExpensive) {
            return;
        }

        $configuredThreshold = config('moontrail.filter_options.distinct_values_warn_threshold');
        $fallbackThreshold = config('moontrail.ui.distinct_values_warn_threshold', 50000);
        $threshold = is_numeric($configuredThreshold)
            ? (int) $configuredThreshold
            : (is_numeric($fallbackThreshold) ? (int) $fallbackThreshold : 50000);

        if ($threshold <= 0) {
            return;
        }

        static $warned = [];
        $table = $model->getTable();
        $key = $table . ':' . $column;

        if (isset($warned[$key])) {
            return;
        }

        $probeCount = $model::query()->limit($threshold + 1)->count();

        if ($probeCount <= $threshold) {
            return;
        }

        $warned[$key] = true;

        Log::warning('MoonTrail distinctValues() may be expensive on large activity table.', [
            'table'        => $table,
            'column'       => $column,
            'threshold'    => $threshold,
            'sample_count' => $probeCount,
        ]);
    }
}
