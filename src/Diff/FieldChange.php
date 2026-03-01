<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

use MoonShine\MoonTrail\Enums\ChangeType;

final readonly class FieldChange
{
    public function __construct(
        public string $field,
        public mixed $oldValue,
        public mixed $newValue,
        public ChangeType $type,
        public bool $masked = false,
    ) {}

    public function isChanged(): bool
    {
        return $this->type !== ChangeType::Unchanged;
    }
}
