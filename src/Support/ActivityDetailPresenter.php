<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Components\ActivityTimeline;
use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Models\ModelVersion;

/**
 * Prepares display data for Activity detail sections and provides
 * reusable HTML rendering helpers for index/detail fields.
 */
final readonly class ActivityDetailPresenter
{
    public function __construct(
        private ActivityFormatterContract $formatter,
        private ActivityRecordFactory $recordFactory,
    ) {}

    // -------------------------------------------------------------------------
    // Data builders for detail section Blade partials
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function generalData(Model $activity): array
    {
        $dateFormat = MoonTrailConfig::uiDateFormat();

        $record = $this->recordFactory->fromModel($activity);
        $rawKey = $record->getId();

        return [
            'id'      => (string) $rawKey,
            'logName' => is_scalar(data_get($activity, 'log_name')) && (string) data_get($activity, 'log_name') !== ''
                ? (string) data_get($activity, 'log_name')
                : null,
            'activity'    => $activity,
            'description' => $this->formatDescription($record),
            'date'        => $record->getCreatedAt()->format($dateFormat),
        ];
    }

    /** @return array<string, mixed> */
    public function relationsData(Model $activity): array
    {
        $record = $this->recordFactory->fromModel($activity);

        return [
            'causer' => [
                'morphType' => $record->getCauserType(),
                'morphId'   => $record->getCauserId(),
                'model'     => data_get($activity, 'causer'),
            ],
            'subject' => [
                'morphType' => $record->getSubjectType(),
                'morphId'   => $record->getSubjectId(),
                'model'     => data_get($activity, 'subject'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function changesData(Model $activity): array
    {
        $record = $this->recordFactory->fromModel($activity);

        return [
            'changes' => DiffComputer::fromActivity($record),
        ];
    }

    /**
     * Returns an empty array when there is nothing to render (no subject or no versions).
     *
     * @return array<string, mixed>
     */
    public function historyData(Model $activity): array
    {
        $record = $this->recordFactory->fromModel($activity);

        if ($record->getSubjectType() === null || $record->getSubjectId() === null) {
            return [];
        }

        $perPage = MoonTrailConfig::uiPerPage();

        $versions = ModelVersion::query()
            ->where('versionable_type', $record->getSubjectType())
            ->where('versionable_id', $record->getSubjectId())
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

    public function formatDescription(ActivityRecordContract $activity): string
    {
        $formatted = $this->formatter->format($activity);
        $description = $formatted['description'];

        if ($description !== '') {
            return $description;
        }

        return $activity->getDescription() ?: '—';
    }
}
