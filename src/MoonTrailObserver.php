<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use MoonShine\MoonTrail\Support\MoonTrailConfig;
use MoonShine\MoonTrail\Support\MoonTrailLogger;
use Throwable;

use function array_diff_key;
use function array_flip;
use function class_basename;
use function class_uses_recursive;
use function in_array;
use function method_exists;

final class MoonTrailObserver
{
    private static bool $suspended = false;

    public function __construct(
        private readonly VersionManagerContract $versionManager,
        private readonly ActivityLoggerContract $activityLogger,
        private readonly MoonTrailLogger $logger,
    ) {}

    public static function suspend(): void
    {
        self::$suspended = true;
    }

    public static function resume(): void
    {
        self::$suspended = false;
    }

    public static function isSuspended(): bool
    {
        return self::$suspended;
    }

    public function created(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        $this->handle($model, ActivityEvent::Created, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        /** @var array<string, mixed> $original */
        $original = $model->getOriginal();

        $this->handle($model, ActivityEvent::Updated, $original, $model->getAttributes());
    }

    public function deleted(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        /** @var array<string, mixed> $original */
        $original = $model->getOriginal();

        $this->handle($model, ActivityEvent::Deleted, $original, []);
    }

    public function restored(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        $this->handle($model, ActivityEvent::Restored, [], $model->getAttributes());
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $attributes
     */
    private function handle(Model $model, ActivityEvent $event, array $old, array $attributes): void
    {
        $activityId = null;

        if ($this->shouldLogActivity($model)) {
            $activityId = $this->logActivity($model, $event, $old, $attributes);
        }

        if ($this->canVersion($model)) {
            try {
                $this->versionManager->createVersion($model, $event->value, $activityId);
            } catch (Throwable $e) {
                $this->handleException(
                    exception: $e,
                    operation: 'version_create_failed',
                    model: $model,
                    event: $event,
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $attributes
     */
    private function logActivity(
        Model $model,
        ActivityEvent $event,
        array $old,
        array $attributes,
    ): ?int {
        if (! MoonTrailConfig::autoTrackWriteActivity()) {
            return null;
        }

        /** @var array<int, string> $hiddenFields */
        $hiddenFields = MoonTrailConfig::sensitiveHide();

        $filteredOld = array_diff_key($old, array_flip($hiddenFields));
        $filteredNew = array_diff_key($attributes, array_flip($hiddenFields));

        try {
            return $this->activityLogger->log($model, $event->value, [
                'old'         => $filteredOld,
                'attributes'  => $filteredNew,
                'description' => class_basename($model) . ' was ' . $event->value,
                'causer'      => $this->resolveCauser(),
            ]);
        } catch (Throwable $e) {
            $this->handleException(
                exception: $e,
                operation: 'activity_log_write_failed',
                model: $model,
                event: $event,
            );

            return null;
        }
    }

    /**
     * Whether the observer should create its own activity log entry.
     * Models that already use Spatie's LogsActivity trait log activity
     * on their own — the observer must not double-log for them.
     */
    private function shouldLogActivity(Model $model): bool
    {
        return ! in_array(\Spatie\Activitylog\Traits\LogsActivity::class, class_uses_recursive($model), true);
    }

    /**
     * Whether version snapshots can be created for this model.
     * Requires the model to have a versions() relation (provided by HasMoonTrail).
     */
    private function canVersion(Model $model): bool
    {
        return MoonTrailConfig::versioningEnabled()
            && method_exists($model, 'versions');
    }

    /**
     * Try to resolve the currently authenticated user as activity causer.
     * Checks MoonShine guard first, then the default guard.
     */
    private function resolveCauser(): ?Model
    {
        try {
            /** @var string $guard */
            $guard = config('moonshine.auth.guard', 'moonshine');
            $user = auth($guard)->user();

            if ($user instanceof Model) {
                return $user;
            }

            $defaultUser = auth()->user();

            return $defaultUser instanceof Model ? $defaultUser : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function handleException(Throwable $exception, string $operation, Model $model, ActivityEvent $event): void
    {
        $this->logger->error($operation, $this->logger->withException($exception, [
            'subject_type' => $model->getMorphClass(),
            'subject_id'   => $model->getKey(),
            'event'        => $event->value,
        ]));

        if (MoonTrailConfig::autoTrackOnError() === 'report') {
            report($exception);
        }
    }
}
