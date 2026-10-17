<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Adapters\Livewire\Components;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Support\MoonTrailAuthResolver;
use MoonShine\MoonTrail\Versioning\RollbackAuthorizationResolver;
use MoonShine\MoonTrail\Versioning\RollbackService;

final class ActivityTimeline extends Component
{
    #[Locked]
    public string $modelClass;

    #[Locked]
    public int|string $modelId;

    #[Locked]
    public ?int $selectedVersionId = null;

    #[Locked]
    public ?int $expectedVersion = null;

    #[Locked]
    public ?string $expectedUpdatedAt = null;

    public bool $confirmingRollback = false;

    public function mount(mixed $model = null, ?string $modelClass = null, int|string|null $modelId = null): void
    {
        if ($model instanceof Model) {
            $this->modelClass = $model::class;
            $key = $model->getKey();
            $this->modelId = is_int($key) || is_string($key) ? $key : throw new AuthorizationException('Invalid model key.');
        } elseif ($modelClass !== null && $modelId !== null) {
            $this->modelClass = $modelClass;
            $this->modelId = $modelId;
        } else {
            throw new AuthorizationException('Model or modelClass + modelId required.');
        }

        $this->authorizeModel($this->model());
    }

    public function boot(): void
    {
        if (isset($this->modelClass, $this->modelId)) {
            $this->authorizeModel($this->model());
        }
    }

    public function selectVersion(int $id): void
    {
        $model = $this->model();
        $this->authorizeModel($model);
        app(RollbackAuthorizationResolver::class)->authorize($model);
        $version = $this->version($model, $id);

        $this->selectedVersionId = $version->id;
        $latest = ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->max('version');
        $this->expectedVersion = is_numeric($latest) ? (int) $latest : 0;
        $rawUpdatedAt = $model->getRawOriginal('updated_at');
        $this->expectedUpdatedAt = is_string($rawUpdatedAt) ? $rawUpdatedAt : null;
    }

    public function promptRollback(): void
    {
        $this->confirmingRollback = true;
    }

    public function cancelRollback(): void
    {
        $this->confirmingRollback = false;
    }

    public function rollback(RollbackService $service, RollbackAuthorizationResolver $authorization): void
    {
        $model = $this->model();
        $this->authorizeModel($model);
        $authorization->authorize($model);

        if ($this->selectedVersionId === null || $this->expectedVersion === null) {
            throw new AuthorizationException('Select a version before rollback.');
        }

        $version = $this->version($model, $this->selectedVersionId);

        if ($this->expectedUpdatedAt !== $model->getRawOriginal('updated_at')) {
            $this->addError('rollback', __('moontrail::ui.rollback_modified_refresh'));
            $this->confirmingRollback = false;

            return;
        }

        try {
            $service->rollback($model, $version->version, expectedCurrentVersion: $this->expectedVersion);
        } catch (RollbackConflictException $exception) {
            $this->addError('rollback', $exception->getMessage());
            $this->confirmingRollback = false;

            return;
        }

        $this->confirmingRollback = false;
        $this->selectedVersionId = null;
        $this->expectedVersion = null;
        $this->expectedUpdatedAt = null;
        $this->dispatch('moontrail-rolled-back', modelClass: $this->modelClass, modelId: $this->modelId);
    }

    public function clearSelection(): void
    {
        $this->selectedVersionId = null;
        $this->expectedVersion = null;
        $this->expectedUpdatedAt = null;
        $this->confirmingRollback = false;
    }

    public function render(): View
    {
        $model = $this->model();
        $this->authorizeModel($model);

        $selectedVersion = $this->selectedVersionId !== null
            ? $this->version($model, $this->selectedVersionId)
            : null;

        return view('moontrail::livewire.activity-timeline', [
            'model'           => $model,
            'selectedVersion' => $selectedVersion,
            'versions'        => ModelVersion::query()
                ->with('author')
                ->where('versionable_type', $model->getMorphClass())
                ->where('versionable_id', $model->getKey())
                ->orderByDesc('version')
                ->limit(30)
                ->get(),
            'canRollback' => app(RollbackAuthorizationResolver::class)->canRollback($model),
            'changes'     => $selectedVersion !== null
                ? app(VersionManagerContract::class)->diffWithCurrent($selectedVersion, $model)
                : [],
        ]);
    }

    private function model(): Model
    {
        if (! is_a($this->modelClass, Model::class, true)) {
            throw new AuthorizationException('Unknown model.');
        }

        return $this->modelClass::query()->findOrFail($this->modelId);
    }

    private function version(Model $model, int $id): ModelVersion
    {
        return ModelVersion::query()
            ->with('author')
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->findOrFail($id);
    }

    private function authorizeModel(Model $model): void
    {
        $user = app(MoonTrailAuthResolver::class)->resolveUser();

        if ($user === null || ! Gate::forUser($user)->allows('view', $model)) {
            throw new AuthorizationException('Viewing this history is denied.');
        }
    }
}
