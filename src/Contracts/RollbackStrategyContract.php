<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use MoonShine\MoonTrail\Exceptions\RollbackDeniedException;

interface RollbackStrategyContract
{
    /**
     * @param array<string, mixed>|null $rules
     *
     * @throws ValidationException
     * @throws RollbackConflictException
     * @throws RollbackDeniedException
     */
    public function rollback(Model $model, int $targetVersion, ?array $rules = null): Model;
}
