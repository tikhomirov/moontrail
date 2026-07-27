<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;

it('throws clear exception when custom logger is not manually bound', function (): void {
    config()->set('moontrail.activity_logger', 'custom');

    app()->forgetInstance(ActivityLoggerContract::class);

    expect(static fn () => app(ActivityLoggerContract::class))
        ->toThrow(RuntimeException::class, 'configured_driver=custom; missing_binding=MoonShine\\MoonTrail\\Contracts\\ActivityLoggerContract');
});

it('throws clear exception when custom query is not manually bound', function (): void {
    config()->set('moontrail.activity_logger', 'custom');

    app()->forgetInstance(ActivityQueryContract::class);

    expect(static fn () => app(ActivityQueryContract::class))
        ->toThrow(RuntimeException::class, 'configured_driver=custom; missing_binding=MoonShine\\MoonTrail\\Contracts\\ActivityQueryContract');
});
