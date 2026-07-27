<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Support\MoonTrailConfig;

it('reads the documented activity logger driver', function (): void {
    config()->set('moontrail.activity_logger', 'database');

    expect(MoonTrailConfig::activityDriver())->toBe('database');
});

it('reads versioning settings from documented keys', function (): void {
    config()->set('moontrail.versioning.enabled', false);
    config()->set('moontrail.versioning.max_versions', 10);
    config()->set('moontrail.versioning.overflow_strategy', 'prevent');

    expect(MoonTrailConfig::versioningEnabled())->toBeFalse()
        ->and(MoonTrailConfig::versionLimit())->toBe(10)
        ->and(MoonTrailConfig::versionOnLimit())->toBe('prevent');
});

it('reads auto-tracking models from auto_track_models', function (): void {
    config()->set('moontrail.auto_track_models', [\MoonShine\Laravel\Models\MoonshineUser::class]);

    expect(MoonTrailConfig::autoTrackModels())->toBe([\MoonShine\Laravel\Models\MoonshineUser::class]);
});

it('reads menu models from tracked_models', function (): void {
    config()->set('moontrail.tracked_models', [\MoonShine\Laravel\Models\MoonshineUser::class]);

    expect(MoonTrailConfig::menuModels())->toBe([\MoonShine\Laravel\Models\MoonshineUser::class]);
});

it('reads resource registration from resource.in_menu', function (): void {
    config()->set('moontrail.resource.in_menu', true);
    config()->set('moontrail.resource.class', MoonTrailResource::class);

    expect(MoonTrailConfig::resourceRegister())->toBeTrue()
        ->and(MoonTrailConfig::resourceClass())->toBe(MoonTrailResource::class);
});

it('maps rollback booleans to validation strategy', function (): void {
    config()->set('moontrail.rollback.validate', false);
    config()->set('moontrail.rollback.require_rules', false);

    expect(MoonTrailConfig::rollbackValidation())->toBe('none');

    config()->set('moontrail.rollback.validate', true);
    config()->set('moontrail.rollback.require_rules', false);

    expect(MoonTrailConfig::rollbackValidation())->toBe('if_rules_provided');

    config()->set('moontrail.rollback.validate', true);
    config()->set('moontrail.rollback.require_rules', true);

    expect(MoonTrailConfig::rollbackValidation())->toBe('required');
});

it('reads filter options from documented keys', function (): void {
    config()->set('moontrail.filter_options.strategy', 'static');
    config()->set('moontrail.filter_options.cache.enabled', true);
    config()->set('moontrail.filter_options.cache.ttl', 120);

    expect(MoonTrailConfig::filterSource())->toBe('static')
        ->and(MoonTrailConfig::filterCacheEnabled())->toBeTrue()
        ->and(MoonTrailConfig::filterCacheTtl())->toBe(120);
});

it('falls back to ui keys for performance warnings', function (): void {
    config()->set('moontrail.filter_options.warn_on_expensive_distinct_values');
    config()->set('moontrail.ui.warn_on_expensive_distinct_values', false);

    expect(MoonTrailConfig::filterWarnOnExpensiveQueries())->toBeFalse();

    config()->set('moontrail.filter_options.distinct_values_warn_threshold');
    config()->set('moontrail.ui.distinct_values_warn_threshold', 12345);

    expect(MoonTrailConfig::filterWarnThreshold())->toBe(12345);
});
