<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Support\ActivityRuntimeResolver;
use Spatie\Activitylog\Models\Activity;

it('resolves auto driver to spatie when spatie is installed', function (): void {
    config()->set('moontrail.activity_logger', 'auto');

    $runtime = app(ActivityRuntimeResolver::class)->resolve(spatieInstalled: true);

    expect($runtime->configuredDriver)->toBe('auto')
        ->and($runtime->resolvedDriver)->toBe('spatie')
        ->and($runtime->activityModel)->toBe(Activity::class)
        ->and($runtime->supportsModelDetail)->toBeTrue()
        ->and($runtime->supportsDynamicFilterOptions)->toBeTrue();
});

it('resolves auto driver to database when spatie is not installed', function (): void {
    config()->set('moontrail.activity_logger', 'auto');

    $runtime = app(ActivityRuntimeResolver::class)->resolve(spatieInstalled: false);

    expect($runtime->configuredDriver)->toBe('auto')
        ->and($runtime->resolvedDriver)->toBe('database')
        ->and($runtime->activityModel)->toBe(MoonTrailActivity::class)
        ->and($runtime->supportsModelDetail)->toBeTrue()
        ->and($runtime->supportsDynamicFilterOptions)->toBeTrue();
});

it('resolves custom driver and uses MoonTrailActivity as placeholder', function (): void {
    config()->set('moontrail.activity_logger', 'custom');

    $runtime = app(ActivityRuntimeResolver::class)->resolve();

    expect($runtime->configuredDriver)->toBe('custom')
        ->and($runtime->resolvedDriver)->toBe('custom')
        ->and($runtime->activityModel)->toBe(MoonTrailActivity::class)
        ->and($runtime->supportsModelDetail)->toBeFalse()
        ->and($runtime->supportsDynamicFilterOptions)->toBeFalse();
});

it('throws for invalid activity logger driver', function (): void {
    config()->set('moontrail.activity_logger', 'weird-driver');

    expect(static fn () => app(ActivityRuntimeResolver::class)->resolve())
        ->toThrow(RuntimeException::class, 'Unsupported moontrail.activity_logger: weird-driver');
});
