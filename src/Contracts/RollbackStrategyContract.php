<?php

declare(strict_types=1);

namespace MoonShine\ActivityLog\Contracts;

use Illuminate\Database\Eloquent\Model;

interface RollbackStrategyContract
{
    public function rollback(Model $model, int $targetVersion, ?array $rules = null): Model;
}
