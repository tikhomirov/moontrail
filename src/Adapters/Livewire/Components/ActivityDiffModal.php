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
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Support\ActivityAuthorizationResolver;
use MoonShine\MoonTrail\Support\MoonTrailConfig;

final class ActivityDiffModal extends Component
{
    public bool $isOpen = false;

    #[Locked]
    public ?int $activityId = null;

    public ?string $errorMessage = null;

    #[On('open-moontrail-diff')]
    public function open(int|string $activityId): void
    {
        $this->errorMessage = null;
        $this->activityId = (int) $activityId;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->activityId = null;
        $this->errorMessage = null;
    }

    public function render(): View
    {
        $changes = [];
        $event = null;
        $description = null;
        $causer = null;
        $date = null;
        $subjectName = null;
        $subjectType = null;
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
                    $changes = DiffComputer::fromActivity($record);
                    $event = $record->getEvent();
                    $description = $record->getDescription();
                    $date = $record->getCreatedAt()->format(MoonTrailConfig::uiDateFormat());

                    $rawModel = $record instanceof ModelBackedActivityRecordContract ? $record->model() : null;
                    $causerObj = $rawModel?->getAttribute('causer');
                    $causerId = $record->getCauserId();
                    $causer = is_object($causerObj) && isset($causerObj->name)
                        ? (string) $causerObj->name
                        : (is_scalar($causerId) && (string) $causerId !== '' ? ('ID: ' . $causerId) : __('moontrail::ui.system'));

                    $rawSubjectType = $record->getSubjectType();
                    $subjectType = is_string($rawSubjectType) ? class_basename($rawSubjectType) : '—';
                    $subjectObj = $rawModel?->getAttribute('subject');
                    $subjectId = $record->getSubjectId();
                    $subjectName = is_object($subjectObj) && isset($subjectObj->name)
                        ? (string) $subjectObj->name
                        : ($subjectType . ' #' . (is_scalar($subjectId) ? (string) $subjectId : ''));
                } catch (AuthorizationException) {
                    $errorMessage = __('moontrail::ui.rollback_denied_hint');
                }
            }
        }

        return view('moontrail::livewire.activity-diff-modal', [
            'changes'      => $changes,
            'event'        => $event,
            'description'  => $description,
            'causer'       => $causer,
            'date'         => $date,
            'subjectName'  => $subjectName,
            'subjectType'  => $subjectType,
            'errorMessage' => $errorMessage,
        ]);
    }
}
