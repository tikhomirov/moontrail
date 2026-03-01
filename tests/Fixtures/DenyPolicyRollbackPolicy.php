<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

final class DenyPolicyRollbackPolicy
{
    public function rollback(object $user, DenyPolicyRollbackPost $post): bool
    {
        unset($user, $post);

        return false;
    }
}
