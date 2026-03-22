<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Logging;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;

final class NullActivityLogger implements ActivityLoggerContract
{
    public function log(Model $model, string $event, array $data = []): ?int
    {
        return null;
    }
}
