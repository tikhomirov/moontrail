<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Components;

use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(array<string, FieldChange> $changes, bool $compactMode = false)
 */
final class DiffViewer extends MoonShineComponent
{
    protected string $view = 'moontrail::components.diff-viewer';

    /**
     * @param array<string, FieldChange> $changes
     */
    public function __construct(
        private readonly array $changes,
        private bool $compactMode = false,
        private bool $onlyChanged = false,
    ) {
        parent::__construct();
    }

    public function compact(): static
    {
        $this->compactMode = true;

        return $this;
    }

    public function onlyChanged(): static
    {
        $this->onlyChanged = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        $changes = $this->changes;

        if ($this->onlyChanged) {
            $changes = array_filter(
                $changes,
                static fn (FieldChange $change): bool => $change->isChanged(),
            );
        }

        return [
            'changes' => $changes,
            'compact' => $this->compactMode,
        ];
    }
}
