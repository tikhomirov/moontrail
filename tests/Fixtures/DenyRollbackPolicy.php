<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

final class DenyRollbackPolicy
{
    public function rollback(object $user, TestPost $post): bool
    {
        unset($user, $post);

        return false;
    }
}
