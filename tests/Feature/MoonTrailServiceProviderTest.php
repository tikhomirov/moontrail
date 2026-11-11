<?php

declare(strict_types=1);

use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\MoonTrail\MoonTrailServiceProvider;
use MoonShine\MoonTrail\Resources\MoonTrailResource;

it('registers MoonTrail resource when CoreContract is already resolved', function (): void {
    $core = app(CoreContract::class);

    $resourceClasses = $core->getResources()
        ->map(static fn (object $resource): string => $resource::class)
        ->toArray();

    expect($resourceClasses)->toContain(MoonTrailResource::class);
});

it('keeps the MoonShine UI for configs published before ui_adapter existed', function (): void {
    $legacy = config('moontrail');
    unset($legacy['ui_adapter']);
    config()->set('moontrail', $legacy);

    (new MoonTrailServiceProvider(app()))->register();

    expect(config('moontrail.ui_adapter'))->toBe('moonshine');
});

it('does not override an explicit ui_adapter', function (): void {
    config()->set('moontrail.ui_adapter', 'headless');

    (new MoonTrailServiceProvider(app()))->register();

    expect(config('moontrail.ui_adapter'))->toBe('headless');
});
