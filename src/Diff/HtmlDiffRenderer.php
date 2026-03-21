<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

use MoonShine\MoonTrail\Contracts\DiffRendererContract;

final class HtmlDiffRenderer implements DiffRendererContract
{
    /**
     * @param array<string, FieldChange> $changes
     */
    public function render(array $changes): string
    {
        return view('moontrail::components.diff-viewer', [
            'changes' => $changes,
            'compact' => false,
        ])->render();
    }
}
