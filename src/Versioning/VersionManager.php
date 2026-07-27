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
use MoonShine\MoonTrail\Support\ActivityModelResolver;
use MoonShine\MoonTrail\Support\MoonTrailConfig;
use MoonShine\MoonTrail\Support\MoonTrailLogger;

final readonly class VersionManager implements VersionManagerContract
{
    public function __construct(
        private ActivityModelResolver $activityModelResolver,
        private MoonTrailLogger $logger,
    ) {}

    public function createVersion(Model $model, string $event, ?int $activityId = null): ModelVersion
    {
        $this->enforceLimit($model);

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
            'activity_id'         => $activityId ?? $this->resolveActivityId($model),
            'author_type'         => $author instanceof Model ? $author->getMorphClass() : null,
            'author_id'           => Auth::id(),
            'event'               => $event,
            'is_rollback'         => false,
            'rollback_to_version' => null,
        ]);

        event(new VersionCreated($model, $version));

        return $version;
    }

    public function enforceLimit(Model $model): void
    {
        $this->enforceLimitBeforeInsert($model);
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
        $hiddenFields = MoonTrailConfig::sensitiveHide();

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
        $hiddenFields = MoonTrailConfig::sensitiveHide();

        return DiffComputer::compute(
            before: $version->snapshot,
            after: $model->attributesToArray(),
            hiddenFields: $hiddenFields,
        );
    }

    private function enforceLimitBeforeInsert(Model $model): void
    {
        $maxVersions = MoonTrailConfig::versionLimit();

        if ($maxVersions <= 0) {
            return;
        }

        $query = ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey());
        $count = (int) $query->count();

        if ($count < $maxVersions) {
            return;
        }

        $overflowStrategy = MoonTrailConfig::versionOnLimit();

        $rawModelKey = $model->getKey();
        $modelKeyStr = is_int($rawModelKey) || is_string($rawModelKey) ? (string) $rawModelKey : '0';

        if ($overflowStrategy === 'prevent') {
            $this->logger->warning('version_limit_exceeded', [
                'subject_type' => $model->getMorphClass(),
                'subject_id'   => $rawModelKey,
                'max_versions' => $maxVersions,
                'count'        => $count,
            ]);

            throw new VersionLimitExceededException(
                sprintf(
                    'Version limit reached for %s#%s: count=%d, max_versions=%d, strategy=prevent.',
                    $model->getMorphClass(),
                    $modelKeyStr,
                    $count,
                    $maxVersions,
                ),
            );
        }

        // Pre-insert pruning must leave room for the next version row.
        $toDelete = ($count - $maxVersions) + 1;

        ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->orderBy('version')
            ->limit($toDelete)
            ->delete();
    }

    /**
     * Try to find the latest Spatie activity_id for this model.
     * Returns null if Spatie is not installed.
     */
    private function resolveActivityId(Model $model): ?int
    {
        $activityModel = $this->activityModelResolver->resolveModelClass();

        /** @var ?Model $activity */
        $activity = $activityModel::query()
            ->where('subject_type', $model->getMorphClass())
            ->where('subject_id', $model->getKey())
            ->latest('id')
            ->first();

        $key = $activity?->getKey();

        return is_int($key) ? $key : null;
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
}
