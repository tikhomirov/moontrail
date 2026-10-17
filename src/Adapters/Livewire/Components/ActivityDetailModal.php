<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Adapters\Livewire\Components;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Support\ActivityAuthorizationResolver;
use MoonShine\MoonTrail\Support\ActivityDetailPresenter;

final class ActivityDetailModal extends Component
{
    public bool $isOpen = false;

    #[Locked]
    public ?int $activityId = null;

    public ?string $activeTab = 'general';

    public ?string $errorMessage = null;

    #[On('open-moontrail-detail')]
    public function open(int|string $activityId): void
    {
        $this->errorMessage = null;
        $this->activityId = (int) $activityId;
        $this->activeTab = 'general';
        $this->isOpen = true;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->activityId = null;
        $this->errorMessage = null;
    }

    public function render(): View
    {
        $general = [];
        $relations = [];
        /** @var array<string, FieldChange> $changes */
        $changes = [];
        $errorMessage = $this->errorMessage;

        if ($this->isOpen && $this->activityId !== null && $errorMessage === null) {
            /** @var ActivityQueryContract<Model> $activityQuery */
            $activityQuery = app(ActivityQueryContract::class);
            $record = $activityQuery->find($this->activityId);

            if (! $record instanceof ActivityRecordContract) {
                $errorMessage = __('moontrail::ui.no_records_found');
            } else {
                try {
                    app(ActivityAuthorizationResolver::class)->authorize($record);
                    $rawModel = $record instanceof ModelBackedActivityRecordContract ? $record->model() : null;

                    if ($rawModel instanceof Model) {
                        /** @var ActivityDetailPresenter $presenter */
                        $presenter = app(ActivityDetailPresenter::class);
                        $general = $presenter->generalData($rawModel);
                        $relations = $presenter->relationsData($rawModel);
                        $changes = $presenter->changesData($rawModel)['changes'] ?? [];
                    } else {
                        $errorMessage = __('moontrail::ui.no_records_found');
                    }
                } catch (AuthorizationException) {
                    $errorMessage = __('moontrail::ui.rollback_denied_hint');
                }
            }
        }

        return view('moontrail::livewire.activity-detail-modal', [
            'general'      => $general,
            'relations'    => $relations,
            'changes'      => $changes,
            'errorMessage' => $errorMessage,
        ]);
    }
}
