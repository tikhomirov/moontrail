<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Support\ActivityModelResolver;
use MoonShine\MoonTrail\Support\ActivityRuntime;
use MoonShine\MoonTrail\Support\ActivityRuntimeResolver;
use MoonShine\MoonTrail\Tests\Fixtures\TestCustomActivity;

it('resolves model class via activity runtime metadata', function (): void {
    $resolver = app(ActivityModelResolver::class);
    $runtime = app(ActivityRuntime::class);

    expect($resolver->resolve())->toBe($runtime->activityModel);
});

it('throws clear exception when custom activity_model class does not exist', function (): void {
    config()->set('moontrail.activity_logger', 'custom');
    config()->set('moontrail.activity_model', 'App\\Models\\DoesNotExist');

    expect(static fn () => app(ActivityRuntimeResolver::class)->resolve())
        ->toThrow(RuntimeException::class, 'Invalid moontrail.activity_model for configured driver "custom": class "App\\Models\\DoesNotExist" does not exist');
});

it('throws clear exception when custom activity_model is not eloquent model', function (): void {
    config()->set('moontrail.activity_logger', 'custom');
    config()->set('moontrail.activity_model', stdClass::class);

    expect(static fn () => app(ActivityRuntimeResolver::class)->resolve())
        ->toThrow(RuntimeException::class, 'Invalid moontrail.activity_model for configured driver "custom": class "stdClass" must extend');
});

it('resolves custom activity model when it is valid', function (): void {
    config()->set('moontrail.activity_logger', 'custom');
    config()->set('moontrail.activity_model', TestCustomActivity::class);

    $runtime = app(ActivityRuntimeResolver::class)->resolve();

    expect($runtime->activityModel)->toBe(TestCustomActivity::class);
});
