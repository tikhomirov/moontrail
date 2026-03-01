<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Versioning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Events\VersionCreated;
use MoonShine\MoonTrail\Exceptions\VersionLimitExceededException;
use MoonShine\MoonTrail\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

final class VersionManager implements VersionManagerContract
{
    public function createVersion(Model $model, string $event, ?Activity $activity = null): ModelVersion
    {
        $currentVersion = ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->max('version');

        $author = Auth::user();

        $version = ModelVersion::query()->create([
            'versionable_type'    => $model->getMorphClass(),
            'versionable_id'      => $model->getKey(),
            'version'             => (is_numeric($currentVersion) ? (int) $currentVersion : 0) + 1,
            'snapshot'            => $this->snapshot($model),
            'activity_id'         => $activity?->getKey() ?? $this->resolveActivityId($model),
            'author_type'         => $author instanceof Model ? $author->getMorphClass() : null,
            'author_id'           => Auth::id(),
            'event'               => $event,
            'is_rollback'         => false,
            'rollback_to_version' => null,
        ]);

        $this->enforceLimit($model);

        event(new VersionCreated($model, $version));

        return $version;
    }

    public function getVersion(Model $model, int $version): ?ModelVersion
    {
        return ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->where('version', $version)
            ->first();
    }

    /**
     * @return array<string, FieldChange>
     */
    public function diff(ModelVersion $from, ModelVersion $to): array
    {
        /** @var array<int, string> $hiddenFields */
        $hiddenFields = config('moontrail.ui.hidden_fields', []);

        return DiffComputer::compute(
            before: $from->snapshot,
            after: $to->snapshot,
            hiddenFields: $hiddenFields,
        );
    }

    /**
     * @return array<string, FieldChange>
     */
    public function diffWithCurrent(ModelVersion $version, Model $model): array
    {
        /** @var array<int, string> $hiddenFields */
        $hiddenFields = config('moontrail.ui.hidden_fields', []);

        return DiffComputer::compute(
            before: $version->snapshot,
            after: $model->attributesToArray(),
            hiddenFields: $hiddenFields,
        );
    }

    public function enforceLimit(Model $model): void
    {
        $rawMax = config('moontrail.versioning.max_versions');
        $maxVersions = is_numeric($rawMax) ? (int) $rawMax : 0;

        if ($maxVersions <= 0) {
            return;
        }

        $query = ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey());
        $count = (int) $query->count();

        if ($count <= $maxVersions) {
            return;
        }

        $rawStrategy = config('moontrail.versioning.overflow_strategy');
        $overflowStrategy = is_string($rawStrategy) ? $rawStrategy : 'delete_oldest';

        if ($overflowStrategy === 'prevent') {
            throw new VersionLimitExceededException('Version limit exceeded.');
        }

        $toDelete = $count - $maxVersions;

        ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->orderBy('version')
            ->limit($toDelete)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Model $model): array
    {
        $attributes = $model->attributesToArray();

        if (method_exists($model, 'getVersionExcludedFields')) {
            /** @var array<int, string> $excluded */
            $excluded = $model->getVersionExcludedFields();

            foreach ($excluded as $field) {
                unset($attributes[$field]);
            }
        }

        return $attributes;
    }

    private function resolveActivityId(Model $model): ?int
    {
        /** @var ?Activity $activity */
        $activity = Activity::query()
            ->where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->latest('id')
            ->first();

        $key = $activity?->getKey();

        return is_int($key) ? $key : null;
    }
}
