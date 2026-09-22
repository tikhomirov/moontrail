<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\MoonShineAuth;
use Throwable;

use function is_int;
use function is_string;

final class MoonTrailAuthResolver
{
    public function resolveUser(): ?Model
    {
        try {
            $guard = MoonShineAuth::getGuard();

            if ($guard->check()) {
                $user = $guard->user();

                if ($user instanceof Model) {
                    return $user;
                }
            }

            $defaultUser = auth()->user();

            return $defaultUser instanceof Model ? $defaultUser : null;
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
