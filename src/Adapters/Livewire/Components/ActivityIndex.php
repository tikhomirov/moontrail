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

    public function mount(bool $showKpi = true): void
    {
        $this->showKpi = $showKpi;
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
        $this->reset(['search', 'event', 'logName', 'subjectType', 'dateFrom', 'dateUntil']);
        $this->resetPage();
    }

    public function removeFilter(string $key): void
    {
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
        $filters['page'] = $this->getPage();

        /** @var LengthAwarePaginator<int, Model> $activities */
        $activities = $activityQuery->paginate($filters);
        $stats = $this->showKpi ? $activityQuery->stats() : [];

        $kpiCards = $this->buildKpiCards($stats);

        return view('moontrail::livewire.activity-index', [
            'activities'         => $activities,
            'kpiCards'           => $kpiCards,
            'logNameOptions'     => $options->logNames(),
            'eventOptions'       => $options->events(),
            'subjectTypeOptions' => $options->subjectTypes(),
            'dateFormat'         => MoonTrailConfig::uiDateFormat(),
        ]);
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
}
