<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

final class DeniedRollbackPost extends TestPost
{
    public function isRollbackAllowed(): bool
    {
        return false;
    }
}
