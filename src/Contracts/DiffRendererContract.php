<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use MoonShine\MoonTrail\Diff\FieldChange;

interface DiffRendererContract
{
    /**
     * @param array<string, FieldChange> $changes
     */
    public function render(array $changes): string;
}
