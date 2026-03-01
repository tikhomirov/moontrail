<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

use Illuminate\Contracts\Support\Htmlable;
use MoonShine\MoonTrail\Components\DiffViewer;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;

final class HtmlDiffRenderer implements DiffRendererContract
{
    /**
     * @param array<string, FieldChange> $changes
     */
    public function render(array $changes): string
    {
        $rendered = DiffViewer::make(changes: $changes)->render();

        return is_string($rendered)
            ? $rendered
            : ($rendered instanceof Htmlable ? $rendered->toHtml() : '');
    }
}
