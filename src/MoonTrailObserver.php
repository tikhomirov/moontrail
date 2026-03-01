<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Throwable;

use function array_diff_key;
use function array_flip;
use function class_basename;
use function class_uses_recursive;
use function config;
use function in_array;
use function method_exists;

final class MoonTrailObserver
{
    private static bool $suspended = false;

    public function __construct(
        private readonly VersionManagerContract $versionManager,
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

        $activity = $this->shouldLogActivity($model)
            ? $this->logActivity($model, ActivityEvent::Created, [], $model->getAttributes())
            : null;

        if ($this->canVersion($model)) {
            $this->versionManager->createVersion($model, ActivityEvent::Created->value, $activity);
        }
    }

    public function updated(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        /** @var array<string, mixed> $original */
        $original = $model->getOriginal();

        $activity = $this->shouldLogActivity($model)
            ? $this->logActivity($model, ActivityEvent::Updated, $original, $model->getAttributes())
            : null;

        if ($this->canVersion($model)) {
            $this->versionManager->createVersion($model, ActivityEvent::Updated->value, $activity);
        }
    }

    public function deleted(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        /** @var array<string, mixed> $original */
        $original = $model->getOriginal();

        $activity = $this->shouldLogActivity($model)
            ? $this->logActivity($model, ActivityEvent::Deleted, $original, [])
            : null;

        if ($this->canVersion($model)) {
            $this->versionManager->createVersion($model, ActivityEvent::Deleted->value, $activity);
        }
    }

    public function restored(Model $model): void
    {
        if (self::$suspended) {
            return;
        }

        $activity = $this->shouldLogActivity($model)
            ? $this->logActivity($model, ActivityEvent::Restored, [], $model->getAttributes())
            : null;

        if ($this->canVersion($model)) {
            $this->versionManager->createVersion($model, ActivityEvent::Restored->value, $activity);
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
    ): ?Activity {
        if (! config('moontrail.auto_track.log_to_activity', true)) {
            return null;
        }

        /** @var array<int, string> $hiddenFields */
        $hiddenFields = (array) config('moontrail.ui.hidden_fields', []);

        $filteredOld = array_diff_key($old, array_flip($hiddenFields));
        $filteredNew = array_diff_key($attributes, array_flip($hiddenFields));

        try {
            $builder = activity()
                ->performedOn($model)
                ->event($event->value)
                ->withProperties([
                    'old'        => $filteredOld,
                    'attributes' => $filteredNew,
                ]);

            $causer = $this->resolveCauser();

            if ($causer instanceof Model) {
                $builder->causedBy($causer);
            }

            /** @var Activity $activity */
            $activity = $builder->log(class_basename($model) . ' was ' . $event->value);

            return $activity;
        } catch (Throwable) {
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
        return ! in_array(LogsActivity::class, class_uses_recursive($model), true);
    }

    /**
     * Whether version snapshots can be created for this model.
     * Requires the model to have a versions() relation (provided by HasMoonTrail).
     */
    private function canVersion(Model $model): bool
    {
        return (bool) config('moontrail.versioning.enabled', true)
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
}
