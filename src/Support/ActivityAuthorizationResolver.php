<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;

use function class_exists;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;

final class ActivityAuthorizationResolver
{
    /**
     * Authorize viewing the activity record and its diff.
     *
     * @throws AuthorizationException
     */
    public function authorize(ActivityRecordContract $activity): void
    {
        $guard = MoonShineAuth::getGuard();
        $user = $guard->user();

        $subjectType = $activity->getSubjectType();
        $subjectId = $activity->getSubjectId();

        if ($subjectType === null || ! class_exists($subjectType)) {
            return;
        }

        $subject = null;

        if ($activity instanceof ModelBackedActivityRecordContract) {
            $rawSubject = $activity->model()->getAttribute('subject') ?? $activity->model()->getAttribute('model');

            if ($rawSubject instanceof Model) {
                $subject = $rawSubject;
            }
        }

        if (! $subject instanceof Model && (is_int($subjectId) || is_string($subjectId))) {
            /** @var class-string<Model> $subjectType */
            $subject = $subjectType::query()->find($subjectId);
        }

        $policy = Gate::getPolicyFor($subjectType);

        if ($subject instanceof Model && is_object($policy) && method_exists($policy, 'view')) {
            if ($user === null) {
                throw new AuthorizationException('MoonShine user is not authenticated.');
            }

            Gate::forUser($user)->authorize('view', $subject);

            return;
        }

        if (is_object($policy) && method_exists($policy, 'viewAny')) {
            if ($user === null) {
                throw new AuthorizationException('MoonShine user is not authenticated.');
            }

            Gate::forUser($user)->authorize('viewAny', $subjectType);
        }
    }

    public function canView(ActivityRecordContract $activity): bool
    {
        try {
            $this->authorize($activity);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }
}
