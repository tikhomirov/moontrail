<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ActivityLoggerContract
{
    /**
     * Log an activity event for the given model.
     *
     * @param array<string, mixed> $data Additional data (old, attributes, description, causer, etc.)
     */
    public function log(Model $model, string $event, array $data = []): ?int;
}
