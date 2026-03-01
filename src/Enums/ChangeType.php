<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Enums;

enum ChangeType: string
{
    case Added = 'added';
    case Removed = 'removed';
    case Modified = 'modified';
    case Unchanged = 'unchanged';
}
