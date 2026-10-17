<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use MoonShine\Laravel\MoonShineAuth;
use Throwable;

use function is_int;
use function is_string;

final class MoonTrailAuthResolver
{
    public function resolveUser(): ?Model
    {
        try {
            $guardName = config('moontrail.auth_guard', 'web');
            $guard = config('moontrail.ui_adapter') === 'moonshine' && class_exists(MoonShineAuth::class)
                ? MoonShineAuth::getGuard()
                : Auth::guard(is_string($guardName) ? $guardName : 'web');

            if ($guard->check()) {
                $user = $guard->user();

                if ($user instanceof Model) {
                    return $user;
                }
            }

            if (config('moontrail.ui_adapter') === 'moonshine') {
                $defaultUser = auth()->user();

                return $defaultUser instanceof Model ? $defaultUser : null;
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function resolveId(): int|string|null
    {
        $user = $this->resolveUser();
        $key = $user?->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function resolveType(): ?string
    {
        $user = $this->resolveUser();

        return $user?->getMorphClass();
    }
}
