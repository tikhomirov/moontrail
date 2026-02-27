<?php

declare(strict_types=1);

namespace MoonShine\ActivityLog\Versioning;

use Illuminate\Database\Eloquent\Model;
use MoonShine\ActivityLog\Contracts\RollbackStrategyContract;

final class RollbackService implements RollbackStrategyContract
{
    public function rollback(Model $model, int $targetVersion, ?array $rules = null): Model
    {
        return $model;
    }
}
