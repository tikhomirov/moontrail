<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Versioning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use MoonShine\MoonTrail\Events\ModelRolledBack;
use MoonShine\MoonTrail\Events\ModelRollingBack;
use MoonShine\MoonTrail\Exceptions\ModelVersionNotFoundException;
use MoonShine\MoonTrail\Exceptions\NoChangesToRollbackException;
use MoonShine\MoonTrail\Exceptions\RollbackCancelledException;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\MoonTrailObserver;
use MoonShine\MoonTrail\Support\MoonTrailLogger;
use Throwable;

final readonly class RollbackService implements RollbackStrategyContract
{
    public function __construct(
        private VersionManagerContract $versionManager,
        private MoonTrailLogger $logger,
    ) {}

    /**
     * Roll back the model to the given snapshot version.
     *
     * Event flow (outside the DB transaction):
     *   1. event()->until(ModelRollingBack) — cancellable pre-event.
     *      If any listener returns false the rollback is aborted and
     *      RollbackCancelledException is thrown without any DB writes.
     *   2. DB::transaction — model fill + save + rollback-version creation.
     *   3. event(ModelRolledBack) — post-event dispatched after commit.
     *
     * @throws ValidationException
     * @throws RollbackConflictException
     * @throws RollbackCancelledException
     * @throws ModelVersionNotFoundException
     */
    public function rollback(Model $model, int $targetVersion, ?array $rules = null): Model
    {
        $this->logger->info('rollback_start', [
            'subject_type'   => $model->getMorphClass(),
            'subject_id'     => $model->getKey(),
            'target_version' => $targetVersion,
        ]);

        $version = $this->versionManager->getVersion($model, $targetVersion);

        if (! $version instanceof ModelVersion) {
            $exception = new ModelVersionNotFoundException("Version {$targetVersion} not found for model.");
            $this->logger->warning('rollback_failed', $this->logger->withException($exception, [
                'subject_type'   => $model->getMorphClass(),
                'subject_id'     => $model->getKey(),
                'target_version' => $targetVersion,
                'reason'         => 'version_not_found',
            ]));

            throw $exception;
        }

        // Pre-event: cancellable — Event::until() stops at the first listener
        // that returns false and itself returns false. A null return means all
        // listeners ran without cancelling.
        if (Event::until(new ModelRollingBack($model, $targetVersion, $version)) === false) {
            $exception = new RollbackCancelledException(
                "Rollback to version {$targetVersion} was cancelled by a listener.",
            );

            $this->logger->warning('rollback_failed', $this->logger->withException($exception, [
                'subject_type'   => $model->getMorphClass(),
                'subject_id'     => $model->getKey(),
                'target_version' => $targetVersion,
                'reason'         => 'cancelled',
            ]));

            throw $exception;
        }

        $payload = $this->filterSnapshot($model, $version->snapshot);

        if ($payload === []) {
            $modelClass = $model::class;
            $exception = new NoChangesToRollbackException(
                "Rollback to version {$targetVersion} produced an empty payload for model {$modelClass}.",
            );

            $this->logger->warning('rollback_noop', $this->logger->withException($exception, [
                'subject_type'   => $model->getMorphClass(),
                'subject_id'     => $model->getKey(),
                'target_version' => $targetVersion,
            ]));

            throw $exception;
        }

        if ($this->shouldValidate($rules)) {
            /** @var array<string, mixed> $validRules */
            $validRules = (array) $rules;
            Validator::make($payload, $validRules)->validate();
        }

        $fromVersionRaw = ModelVersion::query()
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->max('version');

        $fromVersion = is_numeric($fromVersionRaw) ? (int) $fromVersionRaw : 0;

        MoonTrailObserver::suspend();

        try {
            $result = DB::transaction(
                function () use ($model, $payload, $targetVersion, $fromVersion): array {
                    $query = $model->newQuery();

                    if ($this->usesSoftDeletes($model)) {
                        $query = $query->withoutGlobalScope(SoftDeletingScope::class);
                    }

                    /** @var Model $freshModel */
                    $freshModel = $query
                        ->whereKey($model->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $this->usesSoftDeletes($freshModel)
                        && $freshModel->getAttribute('deleted_at') !== null
                        && is_callable([$freshModel, 'restore'])
                    ) {
                        call_user_func([$freshModel, 'restore']);
                    }

                    $freshModel->fill($payload);
                    $freshModel->save();

                    $rollbackVersion = $this->versionManager->createVersion(
                        model: $freshModel,
                        event: ActivityEvent::RolledBack->value,
                    );

                    $rollbackVersion->forceFill([
                        'is_rollback'         => true,
                        'rollback_to_version' => $targetVersion,
                    ])->save();

                    return [$freshModel, $fromVersion, $targetVersion, $rollbackVersion];
                },
            );
        } catch (ValidationException|ModelVersionNotFoundException|RollbackCancelledException $e) {
            $this->logger->warning('rollback_failed', $this->logger->withException($e, [
                'subject_type'   => $model->getMorphClass(),
                'subject_id'     => $model->getKey(),
                'target_version' => $targetVersion,
            ]));

            // Let domain exceptions propagate as-is.
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('rollback_conflict', $this->logger->withException($e, [
                'subject_type'   => $model->getMorphClass(),
                'subject_id'     => $model->getKey(),
                'target_version' => $targetVersion,
            ]));

            throw RollbackConflictClassifier::classify($e);
        } finally {
            MoonTrailObserver::resume();
        }

        /** @var array{0: Model, 1: int, 2: int, 3: ModelVersion} $result */
        [$freshModel, $from, $to, $rollbackVersion] = $result;

        // Post-event dispatched after transaction commit.
        event(new ModelRolledBack(
            model: $freshModel,
            fromVersion: $from,
            toVersion: $to,
            newVersion: $rollbackVersion,
        ));

        $this->logger->info('rollback_success', [
            'subject_type'        => $freshModel->getMorphClass(),
            'subject_id'          => $freshModel->getKey(),
            'from_version'        => $from,
            'target_version'      => $to,
            'new_version'         => $rollbackVersion->version,
            'rollback_to_version' => $rollbackVersion->rollback_to_version,
        ]);

        return $freshModel;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function filterSnapshot(Model $model, array $snapshot): array
    {
        $fillable = $model->getFillable();

        if ($fillable === []) {
            return [];
        }

        return array_intersect_key($snapshot, array_flip($fillable));
    }

    /**
     * @param array<string, mixed>|null $rules
     */
    private function shouldValidate(?array $rules): bool
    {
        if (! config('moontrail.rollback.validate', true)) {
            return false;
        }

        if (! is_array($rules) || $rules === []) {
            // When require_rules=true deny rollback if no rules provided.
            return (bool) config('moontrail.rollback.require_rules', false);
        }

        return true;
    }

    private function usesSoftDeletes(Model|string $model): bool
    {
        $className = is_string($model) ? $model : $model::class;

        return in_array(SoftDeletes::class, class_uses_recursive($className), true);
    }
}
