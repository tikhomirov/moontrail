<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Events;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Models\ModelVersion;

final readonly class ModelRolledBack
{
    public function __construct(
        public Model $model,
        public int $fromVersion,
        public int $toVersion,
        public ModelVersion $newVersion,
    ) {}
}
