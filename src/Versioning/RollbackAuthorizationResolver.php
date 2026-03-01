<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Versioning;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Exceptions\RollbackDeniedException;

/**
 * Single source of truth for rollback authorization.
 *
 * Matrix (secure-by-default):
 *   1. MoonShine guard not authenticated → deny (AuthorizationException)
 *   2. Laravel policy with `rollback` method registered → delegate to policy
 *   3. No policy → check model::isRollbackAllowed()
 *   4. Otherwise → deny (RollbackDeniedException)
 *
 * The same instance is used in both RollbackController (throws) and
 * ActivityTimeline (returns bool via canRollback()).
 */
final class RollbackAuthorizationResolver
{
    /**
     * Authorize or throw.
     *
     * @throws AuthorizationException
     * @throws RollbackDeniedException
     */
    public function authorize(Model $model): void
    {
        $guard = MoonShineAuth::getGuard();

        if (! $guard->check()) {
            throw new AuthorizationException('MoonShine user is not authenticated.');
        }

        $policy = Gate::getPolicyFor($model);

        if (is_object($policy) && method_exists($policy, 'rollback')) {
            Gate::forUser($guard->user())->authorize('rollback', $model);

            return;
        }

        if (method_exists($model, 'isRollbackAllowed') && $model->isRollbackAllowed()) {
            return;
        }

        throw new RollbackDeniedException('Rollback is denied for this model.');
    }

    /**
     * Check without throwing. Used by UI components.
     */
    public function canRollback(Model $model): bool
    {
        $guard = MoonShineAuth::getGuard();

        if (! $guard->check()) {
            return false;
        }

        $policy = Gate::getPolicyFor($model);

        if (is_object($policy) && method_exists($policy, 'rollback')) {
            return Gate::forUser($guard->user())->allows('rollback', $model);
        }

        if (method_exists($model, 'isRollbackAllowed')) {
            return $model->isRollbackAllowed();
        }

        return false;
    }
}
