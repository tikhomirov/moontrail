<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Pages\MoonTrailDetailPage;
use MoonShine\MoonTrail\Pages\MoonTrailIndexPage;
use MoonShine\MoonTrail\Support\ActivityDetailPresenter;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;
use MoonShine\MoonTrail\Support\ActivityLogQuery;
use MoonShine\MoonTrail\Support\ActivityModelResolver;
use MoonShine\MoonTrail\Support\ActivityRecordFactory;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use RuntimeException;

use function in_array;

/**
 * @extends ModelResource<Model>
 */
final class MoonTrailResource extends ModelResource
{
    protected string $model;

    protected string $column = 'id';

    /**
     * @param ActivityQueryContract<Model> $activityQuery
     */
    public function __construct(
        CoreContract $core,
        private readonly Request $request,
        private readonly ActivityDetailPresenter $presenter,
        private readonly ActivityRecordFactory $recordFactory,
        private readonly ActivityLogFilterOptions $filterOptions,
        private readonly ActivityModelResolver $activityModelResolver,
        private readonly ActivityQueryContract $activityQuery,
    ) {
        $this->model = $this->activityModelResolver->resolveModelClass();

        parent::__construct($core);
    }

    public function getTitle(): string
    {
        return (string) __('moontrail::ui.activity_log');
    }

    /**
     * @return iterable<Model>|Collection<array-key, Model>|LazyCollection<array-key, Model>|CursorPaginator<array-key, Model>|Paginator<array-key, Model>
     */
    public function getItems(): iterable|Collection|LazyCollection|CursorPaginator|Paginator
    {
        $filters = ActivityLogFilterData::fromRequestStrict($this->request)->toArray();

        return $this->activityQuery->paginate($filters);
    }

    public function findItem(bool $orFail = false): ?DataWrapperContract
    {
        $record = $this->activityQuery->find((string) $this->getItemID());

        if ($record instanceof ModelBackedActivityRecordContract) {
            return $this->getCaster()->cast($record->model());
        }

        if ($record instanceof \MoonShine\MoonTrail\Contracts\ActivityRecordContract) {
            throw new RuntimeException(
                sprintf(
                    'MoonTrail detail page requires %s from ActivityQueryContract::find(), got %s',
                    ModelBackedActivityRecordContract::class,
                    $record::class,
                ),
            );
        }

        if ($orFail) {
            throw (new ModelNotFoundException)->setModel(Model::class, [(string) $this->getItemID()]);
        }

        return null;
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
                formatted: static function (Model $activity): string {
                    $value = data_get($activity, 'log_name');

                    return is_scalar($value) && (string) $value !== '' ? (string) $value : '—';
                },
            ),

            Preview::make(
                (string) __('moontrail::ui.field_event'),
                'event',
                formatted: static fn (Model $activity): string => view('moontrail::components.event-badge', [
                    'activity' => $activity,
                ])->render(),
            )->sortable(),

            Preview::make(
                (string) __('moontrail::ui.field_subject'),
                'subject_type',
                formatted: static fn (Model $activity): string => view('moontrail::components.entity-link', [
                    'morphType' => data_get($activity, 'subject_type'),
                    'morphId'   => data_get($activity, 'subject_id'),
                    'model'     => data_get($activity, 'subject'),
                ])->render(),
            ),

            Preview::make(
                (string) __('moontrail::ui.field_causer'),
                'causer_type',
                formatted: static fn (Model $activity): string => view('moontrail::components.entity-link', [
                    'morphType' => data_get($activity, 'causer_type'),
                    'morphId'   => data_get($activity, 'causer_id'),
                    'model'     => data_get($activity, 'causer'),
                ])->render(),
            ),

            Text::make(
                (string) __('moontrail::ui.field_description'),
                'description',
                formatted: fn (Model $activity): string => $this->presenter->formatDescription(
                    $this->recordFactory->fromModel($activity),
                ),
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
                formatted: fn (Model $activity): string => $this->renderGeneralSection($activity),
            ),

            // Section 2: Relations
            Preview::make(
                label: '',
                column: 'subject_type',
                formatted: fn (Model $activity): string => $this->renderRelationsSection($activity),
            ),

            // Section 3: Changes
            Preview::make(
                label: '',
                column: 'properties',
                formatted: fn (Model $activity): string => $this->renderChangesSection($activity),
            ),

            // Section 4: History
            Preview::make(
                label: '',
                column: 'causer_type',
                formatted: fn (Model $activity): string => $this->renderHistorySection($activity),
            ),
        ];
    }

    protected function modifyQueryBuilder(BuilderContract $builder): BuilderContract
    {
        $builder = parent::modifyQueryBuilder($builder)
            ->with(['subject', 'causer'])
            ->latest('id');

        return (new ActivityLogQuery)->apply(
            $builder,
            ActivityLogFilterData::fromRequestStrict($this->request),
        );
    }

    protected function filters(): iterable
    {
        $options = $this->filterOptions;

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
        // Search is handled by ActivityQueryContract::paginate().
        return [];
    }

    // -------------------------------------------------------------------------
    // Detail section renderers (delegate to ActivityDetailPresenter + Blade)
    // -------------------------------------------------------------------------

    private function renderGeneralSection(Model $activity): string
    {
        return view('moontrail::pages.detail-general', $this->presenter->generalData($activity))->render();
    }

    private function renderRelationsSection(Model $activity): string
    {
        return view('moontrail::pages.detail-relations', $this->presenter->relationsData($activity))->render();
    }

    private function renderChangesSection(Model $activity): string
    {
        return view('moontrail::pages.detail-changes', $this->presenter->changesData($activity))->render();
    }

    private function renderHistorySection(Model $activity): string
    {
        $data = $this->presenter->historyData($activity);

        if ($data === []) {
            return '';
        }

        return view('moontrail::pages.detail-history', $data)->render();
    }
}
