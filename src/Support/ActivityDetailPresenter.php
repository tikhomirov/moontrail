<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MoonTrail\Components\ActivityTimeline;
use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

/**
 * Prepares display data for Activity detail sections and provides
 * reusable HTML rendering helpers for index/detail fields.
 */
final readonly class ActivityDetailPresenter
{
    public function __construct(
        private ActivityFormatterContract $formatter,
    ) {}

    // -------------------------------------------------------------------------
    // Data builders for detail section Blade partials
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function generalData(Activity $activity): array
    {
        $dateFormat = is_string(config('moontrail.ui.date_format'))
            ? config('moontrail.ui.date_format')
            : 'd.m.Y H:i:s';

        $rawKey = $activity->getKey();

        return [
            'id'      => is_scalar($rawKey) ? (string) $rawKey : '0',
            'logName' => $activity->log_name !== null && $activity->log_name !== ''
                ? (string) $activity->log_name
                : null,
            'activity'       => $activity,
            'description'    => $this->formatDescription($activity),
            'date'           => $activity->created_at?->format($dateFormat) ?? '—',
        ];
    }

    /** @return array<string, mixed> */
    public function relationsData(Activity $activity): array
    {
        return [
            'causer' => [
                'morphType' => $activity->causer_type,
                'morphId'   => $activity->causer_id,
                'model'     => $activity->causer,
            ],
            'subject' => [
                'morphType' => $activity->subject_type,
                'morphId'   => $activity->subject_id,
                'model'     => $activity->subject,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function changesData(Activity $activity): array
    {
        return [
            'changes' => DiffComputer::fromActivity($activity),
        ];
    }

    /**
     * Returns an empty array when there is nothing to render (no subject or no versions).
     *
     * @return array<string, mixed>
     */
    public function historyData(Activity $activity): array
    {
        if ($activity->subject_type === null || $activity->subject_id === null) {
            return [];
        }

        $perPage = is_numeric(config('moontrail.ui.per_page'))
            ? (int) config('moontrail.ui.per_page')
            : 20;

        $versions = ModelVersion::query()
            ->where('versionable_type', $activity->subject_type)
            ->where('versionable_id', $activity->subject_id)
            ->with('author')
            ->latest('version')
            ->limit($perPage)
            ->get()
            ->all();

        if ($versions === []) {
            return [];
        }

        return [
            'timeline' => ActivityTimeline::make(
                label: (string) __('moontrail::ui.history'),
            )->setVersions(array_values($versions)),
        ];
    }

    public function formatDescription(Activity $activity): string
    {
        $formatted = $this->formatter->format($activity);
        $description = $formatted['description'];

        if ($description !== '') {
            return $description;
        }

        return (string) ($activity->description ?: '—');
    }
}
