<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Adapters\Livewire\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use MoonShine\MoonTrail\Support\ActivityFilterOptionsProvider;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;
use MoonShine\MoonTrail\Support\MoonTrailConfig;
use MoonShine\MoonTrail\Support\SvgIcons;

final class ActivityIndex extends Component
{
    use WithPagination;

    /** id, date, event, subject, causer, description, actions. The log column is extra. */
    private const FIXED_COLUMN_COUNT = 7;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'event')]
    public string $event = '';

    #[Url(as: 'log_name')]
    public string $logName = '';

    #[Url(as: 'subject_type')]
    public string $subjectType = '';

    #[Url(as: 'date_from')]
    public string $dateFrom = '';

    #[Url(as: 'date_until')]
    public string $dateUntil = '';

    #[Locked]
    public bool $showKpi = true;

    /** Log this table cannot leave. Empty means the general history. */
    #[Locked]
    public string $onlyLogName = '';

    /**
     * Columns read from properties.attributes. They replace the log column.
     *
     * @var list<array{key: string, label: string}>
     */
    #[Locked]
    public array $attributeColumns = [];

    /**
     * @param list<mixed> $attributeColumns
     */
    public function mount(bool $showKpi = true, string $onlyLogName = '', array $attributeColumns = []): void
    {
        $this->showKpi = $showKpi;
        $this->onlyLogName = $onlyLogName;
        $this->attributeColumns = $this->normalizeColumns($attributeColumns);
        $this->keepLogWithinBounds();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEvent(): void
    {
        $this->resetPage();
    }

    public function updatingLogName(): void
    {
        $this->resetPage();
    }

    public function updatedLogName(): void
    {
        $this->keepLogWithinBounds();
    }

    public function updatingSubjectType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateUntil(): void
    {
        $this->resetPage();
    }

    public function filterByEvent(string $event): void
    {
        $this->event = $this->event === $event ? '' : $event;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'event', 'subjectType', 'dateFrom', 'dateUntil']);
        $this->logName = $this->onlyLogName;
        $this->resetPage();
    }

    public function removeFilter(string $key): void
    {
        if ($key === 'logName' && $this->onlyLogName !== '') {
            return;
        }

        if (in_array($key, ['search', 'event', 'logName', 'subjectType', 'dateFrom', 'dateUntil'], true)) {
            $this->{$key} = '';
            $this->resetPage();
        }
    }

    public function openDiff(int|string $activityId): void
    {
        $this->dispatch('open-moontrail-diff', activityId: $activityId);
    }

    public function openDetail(int|string $activityId): void
    {
        $this->dispatch('open-moontrail-detail', activityId: $activityId);
    }

    public function openTimeline(string $modelClass, int|string $modelId, ?string $title = null): void
    {
        $this->dispatch('open-moontrail-timeline', modelClass: $modelClass, modelId: $modelId, title: $title);
    }

    public function render(): View
    {
        /** @var ActivityQueryContract<Model> $activityQuery */
        $activityQuery = app(ActivityQueryContract::class);
        $options = new ActivityLogFilterOptions(app(ActivityFilterOptionsProvider::class));

        $filters = array_filter([
            'search'       => $this->search !== '' ? $this->search : null,
            'event'        => $this->event !== '' ? $this->event : null,
            'log_name'     => $this->logName !== '' ? $this->logName : null,
            'subject_type' => $this->subjectType !== '' ? $this->subjectType : null,
            'date_from'    => $this->dateFrom !== '' ? $this->dateFrom : null,
            'date_until'   => $this->dateUntil !== '' ? $this->dateUntil : null,
        ]);

        if ($this->onlyLogName !== '') {
            $filters['locked_log'] = true;
        }

        $filters['page'] = $this->getPage();

        /** @var LengthAwarePaginator<int, Model> $activities */
        $activities = $activityQuery->paginate($filters);
        $stats = $this->showKpi && $this->onlyLogName === '' ? $activityQuery->stats() : [];

        $kpiCards = $this->buildKpiCards($stats);

        return view('moontrail::livewire.activity-index', [
            'activities'         => $activities,
            'kpiCards'           => $kpiCards,
            'logNameOptions'     => $options->logNames(),
            'eventOptions'       => $options->events(),
            'subjectTypeOptions' => $options->subjectTypes(),
            'dateFormat'         => MoonTrailConfig::uiDateFormat(),
            'tableColumnCount'   => $this->tableColumnCount(),
            'attributeText'      => fn (Model $activity, string $key): string => $this->attributeText($activity, $key),
        ]);
    }

    private function attributeText(Model $activity, string $key): string
    {
        $value = data_get($activity, 'properties.attributes.' . $key);

        if (! is_scalar($value)) {
            return '—';
        }

        if ((string) $value === '') {
            return '—';
        }

        return (string) $value;
    }

    private function tableColumnCount(): int
    {
        $middleColumns = $this->attributeColumns === [] ? 1 : count($this->attributeColumns);

        return self::FIXED_COLUMN_COUNT + $middleColumns;
    }

    private function keepLogWithinBounds(): void
    {
        if ($this->onlyLogName !== '') {
            $this->logName = $this->onlyLogName;

            return;
        }

        if (in_array($this->logName, MoonTrailConfig::isolatedLogNames(), true)) {
            $this->logName = '';
        }
    }

    /**
     * @param array{total?: int, created?: int, updated?: int, deleted?: int, other?: int} $stats
     * @return list<array{label: string, value: string, modifier: string, icon: string, event: ?string, isActive: bool}>
     */
    private function buildKpiCards(array $stats): array
    {
        if (! $this->showKpi || $stats === []) {
            return [];
        }

        $fmt = static fn (int $n): string => number_format($n, 0, '.', ' ');

        return [
            [
                'label'    => (string) __('moontrail::ui.kpi_total'),
                'value'    => $fmt((int) ($stats['total'] ?? 0)),
                'modifier' => 'total',
                'icon'     => SvgIcons::document('w-4 h-4'),
                'event'    => null,
                'isActive' => $this->event === '',
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_created'),
                'value'    => $fmt((int) ($stats['created'] ?? 0)),
                'modifier' => 'created',
                'icon'     => SvgIcons::created('w-4 h-4'),
                'event'    => ActivityEvent::Created->value,
                'isActive' => $this->event === ActivityEvent::Created->value,
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_updated'),
                'value'    => $fmt((int) ($stats['updated'] ?? 0)),
                'modifier' => 'updated',
                'icon'     => SvgIcons::updated('w-4 h-4'),
                'event'    => ActivityEvent::Updated->value,
                'isActive' => $this->event === ActivityEvent::Updated->value,
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_deleted'),
                'value'    => $fmt((int) ($stats['deleted'] ?? 0)),
                'modifier' => 'deleted',
                'icon'     => SvgIcons::deleted('w-4 h-4'),
                'event'    => ActivityEvent::Deleted->value,
                'isActive' => $this->event === ActivityEvent::Deleted->value,
            ],
            [
                'label'    => (string) __('moontrail::ui.kpi_other'),
                'value'    => $fmt((int) ($stats['other'] ?? 0)),
                'modifier' => 'other',
                'icon'     => SvgIcons::clock('w-4 h-4'),
                'event'    => null,
                'isActive' => false,
            ],
        ];
    }

    /**
     * @param list<mixed> $columns
     * @return list<array{key: string, label: string}>
     */
    private function normalizeColumns(array $columns): array
    {
        $normalized = [];

        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $key = $column['key'] ?? null;
            $label = $column['label'] ?? null;

            if (! is_string($key)) {
                continue;
            }

            if ($key === '') {
                continue;
            }

            if (! is_string($label)) {
                continue;
            }

            if ($label === '') {
                continue;
            }

            $normalized[] = ['key' => $key, 'label' => $label];
        }

        return $normalized;
    }
}
