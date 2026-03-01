<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<TestPost>
 */
final class TestPostResource extends ModelResource
{
    protected string $model = TestPost::class;

    public function getTitle(): string
    {
        return 'Test posts';
    }
}
