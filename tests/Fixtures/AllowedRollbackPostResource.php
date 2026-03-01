<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<AllowedRollbackPost>
 */
final class AllowedRollbackPostResource extends ModelResource
{
    protected string $model = AllowedRollbackPost::class;

    public function getTitle(): string
    {
        return 'Allowed rollback posts';
    }
}
