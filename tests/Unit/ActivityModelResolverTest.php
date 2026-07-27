<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Support\ActivityModelResolver;
use MoonShine\MoonTrail\Support\ActivityRuntime;
use MoonShine\MoonTrail\Support\ActivityRuntimeResolver;

it('resolves model class via activity runtime metadata', function (): void {
    $resolver = app(ActivityModelResolver::class);
    $runtime = app(ActivityRuntime::class);

    expect($resolver->resolve())->toBe($runtime->activityModel);
});

it('resolves MoonTrailActivity as default activity model for database driver', function (): void {
    config()->set('moontrail.activity.driver', 'database');

    $runtime = app(ActivityRuntimeResolver::class)->resolve();

    expect($runtime->activityModel)->toBe(MoonTrailActivity::class);
});

it('resolves MoonTrailActivity as default activity model for custom driver', function (): void {
    config()->set('moontrail.activity.driver', 'custom');

    $runtime = app(ActivityRuntimeResolver::class)->resolve();

    // Custom mode now defers to ActivityQueryContract::modelClass() at resolve time
    // Default placeholder is MoonTrailActivity
    expect($runtime->activityModel)->toBe(MoonTrailActivity::class);
});
