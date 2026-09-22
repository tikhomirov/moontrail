<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MoonTrail\Traits\WithMoonTrailTab;

/**
 * @extends ModelResource<TestPost>
 */
class TestTabPostResource extends ModelResource
{
    use WithMoonTrailTab;

    protected string $model = TestPost::class;

    public function getTitle(): string
    {
        return 'Test Tab Resource';
    }

    public function testBoot(): void
    {
        $this->bootWithMoonTrailTab();
    }

    public function publicLabel(): string
    {
        return $this->activityTabLabel();
    }

    public function publicLimit(): int
    {
        return $this->activityTabLimit();
    }

    public function publicShowRollback(): bool
    {
        return $this->activityTabShowRollback();
    }
}
