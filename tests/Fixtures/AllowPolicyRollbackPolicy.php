<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

final class AllowPolicyRollbackPolicy
{
    public function rollback(object $user, AllowPolicyRollbackPost $post): bool
    {
        unset($user, $post);

        return true;
    }
}
