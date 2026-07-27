<?php

declare(strict_types=1);

use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\MoonTrail\Resources\MoonTrailResource;

it('registers MoonTrail resource when CoreContract is already resolved', function (): void {
    $core = app(CoreContract::class);

    $resourceClasses = $core->getResources()
        ->map(static fn (object $resource): string => $resource::class)
        ->toArray();

    expect($resourceClasses)->toContain(MoonTrailResource::class);
});
