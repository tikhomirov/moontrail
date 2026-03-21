<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MoonTrail\Pages\MoonTrailDetailPage;
use MoonShine\MoonTrail\Pages\MoonTrailIndexPage;
use MoonShine\MoonTrail\Support\ActivityDetailPresenter;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;
use MoonShine\MoonTrail\Support\ActivityLogQuery;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use Spatie\Activitylog\Models\Activity;

use function in_array;

/**
 * @extends ModelResource<Activity>
 */
final class MoonTrailResource extends ModelResource
{
    protected string $model = Activity::class;

    protected string $column = 'id';

    public function getTitle(): string
    {
        return (string) __('moontrail::ui.activity_log');
    }

    /**
     * Read-only: disable all write operations on the audit log.
     */
    protected function isCan(Ability $ability): bool
    {
        if (in_array($ability, [
            Ability::CREATE,
            Ability::UPDATE,
            Ability::DELETE,
            Ability::MASS_DELETE,
        ], true)) {
            return false;
        }

        return parent::isCan($ability);
    }

    protected function pages(): array
    {
        return [
            MoonTrailIndexPage::class,
            MoonTrailDetailPage::class,
        ];
    }

    /**
     * @return iterable<int, object>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make(
                (string) __('moontrail::ui.field_log'),
                'log_name',
                formatted: static fn (Activity $activity): string => (string) ($activity->log_name ?: '—'),
            ),

            Preview::make(
                (string) __('moontrail::ui.field_event'),
                'event',
                formatted: static fn (Activity $activity): string => view('moontrail::components.event-badge', [
                    'activity' => $activity,
                ])->render(),
            )->sortable(),

            Preview::make(
                (string) __('moontrail::ui.field_subject'),
                'subject_type',
                formatted: static fn (Activity $activity): string => view('moontrail::components.entity-link', [
                    'morphType' => $activity->subject_type,
                    'morphId'   => $activity->subject_id,
                    'model'     => $activity->subject,
                ])->render(),
            ),

            Preview::make(
                (string) __('moontrail::ui.field_causer'),
                'causer_type',
                formatted: static fn (Activity $activity): string => view('moontrail::components.entity-link', [
                    'morphType' => $activity->causer_type,
                    'morphId'   => $activity->causer_id,
                    'model'     => $activity->causer,
                ])->render(),
            ),

            Text::make(
                (string) __('moontrail::ui.field_description'),
                'description',
                formatted: fn (Activity $activity): string => $this->presenter()->formatDescription($activity),
            ),

            Date::make(
                (string) __('moontrail::ui.field_date'),
                'created_at',
            )->sortable(),
        ];
    }

    /**
     * @return iterable<int, object>
     */
    protected function detailFields(): iterable
    {
        return [
            // Section 1: General
            Preview::make(
                label: '',
                column: 'id',
                formatted: fn (Activity $activity): string => $this->renderGeneralSection($activity),
            ),

            // Section 2: Relations
            Preview::make(
                label: '',
                column: 'subject_type',
                formatted: fn (Activity $activity): string => $this->renderRelationsSection($activity),
            ),

            // Section 3: Changes
            Preview::make(
                label: '',
                column: 'properties',
                formatted: fn (Activity $activity): string => $this->renderChangesSection($activity),
            ),

            // Section 4: History
            Preview::make(
                label: '',
                column: 'causer_type',
                formatted: fn (Activity $activity): string => $this->renderHistorySection($activity),
            ),
        ];
    }

    protected function modifyQueryBuilder(BuilderContract $builder): BuilderContract
    {
        /** @var EloquentBuilder<Activity> $builder */
        $builder = parent::modifyQueryBuilder($builder)
            ->with(['subject', 'causer'])
            ->latest('id');

        $filters = ActivityLogFilterData::fromRequest();

        return (new ActivityLogQuery)->apply($builder, $filters);
    }

    protected function filters(): iterable
    {
        $options = new ActivityLogFilterOptions;

        return [
            Select::make((string) __('moontrail::ui.field_log'), 'log_name')
                ->options($options->logNames())
                ->nullable(),
            Select::make((string) __('moontrail::ui.field_event'), 'event')
                ->options($options->events())
                ->nullable(),
            Select::make((string) __('moontrail::ui.field_subject_type'), 'subject_type')
                ->options($options->subjectTypes())
                ->nullable(),
            Text::make((string) __('moontrail::ui.field_subject_id'), 'subject_id'),
            Select::make((string) __('moontrail::ui.field_causer_type'), 'causer_type')
                ->options($options->causerTypes())
                ->nullable(),
            Text::make((string) __('moontrail::ui.field_causer_id'), 'causer_id'),
            Date::make((string) __('moontrail::ui.filter_date_from'), 'date_from'),
            Date::make((string) __('moontrail::ui.filter_date_until'), 'date_until'),
        ];
    }

    protected function search(): array
    {
        // Search is handled in modifyQueryBuilder() to support mixed queries
        // like "product 46" (text + numeric IDs) without framework-level conflicts.
        return [];
    }

    // -------------------------------------------------------------------------
    // Detail section renderers (delegate to ActivityDetailPresenter + Blade)
    // -------------------------------------------------------------------------

    private function renderGeneralSection(Activity $activity): string
    {
        return view('moontrail::pages.detail-general', $this->presenter()->generalData($activity))->render();
    }

    private function renderRelationsSection(Activity $activity): string
    {
        return view('moontrail::pages.detail-relations', $this->presenter()->relationsData($activity))->render();
    }

    private function renderChangesSection(Activity $activity): string
    {
        return view('moontrail::pages.detail-changes', $this->presenter()->changesData($activity))->render();
    }

    private function renderHistorySection(Activity $activity): string
    {
        $data = $this->presenter()->historyData($activity);

        if ($data === []) {
            return '';
        }

        return view('moontrail::pages.detail-history', $data)->render();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function presenter(): ActivityDetailPresenter
    {
        /** @var ActivityDetailPresenter $presenter */
        $presenter = app(ActivityDetailPresenter::class);

        return $presenter;
    }
}
