<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Versioning\RollbackAuthorizationResolver;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Traits\WithLabel;

final class ActivityTimeline extends MoonShineComponent
{
    use WithLabel;

    protected string $view = 'moontrail::components.activity-timeline';

    private int $limit = 20;

    private bool $showDiff = true;

    private bool $showRollback = true;

    /** @var list<ModelVersion>|null */
    private ?array $presetVersions = null;

    public function __construct(
        Closure|string $label,
        private ?object $resource = null,
    ) {
        parent::__construct();

        $this->setLabel($label);
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function withoutDiff(): static
    {
        $this->showDiff = false;

        return $this;
    }

    public function withoutRollback(): static
    {
        $this->showRollback = false;

        return $this;
    }

    /**
     * @param list<ModelVersion> $versions
     */
    public function setVersions(array $versions): static
    {
        $this->presetVersions = $versions;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        $canRollback = $this->showRollback && $this->resolveCanRollback();
        $versions = $this->resolveVersions();

        // The earliest version (minimum version number) is not rollback-able.
        $minVersionId = $versions !== []
            ? min(array_map(static fn (ModelVersion $v): int => (int) $v->id, $versions))
            : null;

        return [
            'label'            => $this->getLabel(),
            'versions'         => $versions,
            'showDiff'         => $this->showDiff,
            'showRollback'     => $this->showRollback,
            'canRollback'      => $canRollback,
            'showRollbackHint' => $this->showRollback && ! $canRollback,
            'minVersionId'     => $minVersionId,
            'changesCount'     => $this->computeChangesCount($versions),
        ];
    }

    /**
     * Returns a map of version.id → number of changed fields (non-unchanged).
     * Uses eager-loaded activity relation to avoid N+1.
     *
     * @param list<ModelVersion> $versions
     * @return array<int, int>
     */
    private function computeChangesCount(array $versions): array
    {
        $result = [];

        foreach ($versions as $version) {
            $activity = $version->relationLoaded('activity') ? $version->activity : null;

            if ($activity === null && $version->activity_id !== null) {
                $activity = $version->activity()->first();
            }

            if ($activity === null) {
                $result[$version->id] = 0;
                continue;
            }

            $changes = DiffComputer::fromActivity($activity);
            $result[$version->id] = count(array_filter(
                $changes,
                static fn (\MoonShine\MoonTrail\Diff\FieldChange $c): bool => $c->type->value !== 'unchanged',
            ));
        }

        return $result;
    }

    /**
     * @return list<ModelVersion>
     */
    private function resolveVersions(): array
    {
        if ($this->presetVersions !== null) {
            return $this->presetVersions;
        }

        $item = $this->getCurrentModel();

        if (! $item instanceof Model) {
            return [];
        }

        /** @var list<ModelVersion> */
        return ModelVersion::query()
            ->where('versionable_type', $item->getMorphClass())
            ->where('versionable_id', $item->getKey())
            ->with('author')
            ->latest('version')
            ->limit($this->limit)
            ->get()
            ->all();
    }

    private function getCurrentModel(): ?Model
    {
        $resource = $this->resource;

        if (! $resource instanceof ModelResource) {
            return null;
        }

        $item = $resource->getItem();

        return $item instanceof Model ? $item : null;
    }

    private function resolveCanRollback(): bool
    {
        $model = $this->getCurrentModel();

        if (! $model instanceof Model) {
            return false;
        }

        return app(RollbackAuthorizationResolver::class)->canRollback($model);
    }
}
