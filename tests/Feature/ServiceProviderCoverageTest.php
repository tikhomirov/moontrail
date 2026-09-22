<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Activity\DatabaseActivityQuery;
use MoonShine\MoonTrail\Activity\NullActivityQuery;
use MoonShine\MoonTrail\Activity\SpatieActivityQuery;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Contracts\VersionManagerContract;
use MoonShine\MoonTrail\Logging\DatabaseActivityLogger;
use MoonShine\MoonTrail\Logging\NullActivityLogger;
use MoonShine\MoonTrail\Logging\SpatieActivityLogger;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Support\ActivityRuntime;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use Spatie\Activitylog\Models\Activity;

it('tests service provider bindings for different activity logger drivers', function (): void {
    // Database driver
    app()->instance(ActivityRuntime::class, new ActivityRuntime('database', 'database', MoonTrailActivity::class, true, true));
    app()->forgetInstance(ActivityLoggerContract::class);
    app()->forgetInstance(ActivityQueryContract::class);
    $logger = app(ActivityLoggerContract::class);
    expect($logger)->toBeInstanceOf(DatabaseActivityLogger::class);

    $query = app(ActivityQueryContract::class);
    expect($query)->toBeInstanceOf(DatabaseActivityQuery::class);

    // Spatie driver
    if (class_exists(Activity::class)) {
        app()->instance(ActivityRuntime::class, new ActivityRuntime('spatie', 'spatie', Activity::class, true, true));
        app()->forgetInstance(ActivityLoggerContract::class);
        app()->forgetInstance(ActivityQueryContract::class);
        $spatieLogger = app(ActivityLoggerContract::class);
        expect($spatieLogger)->toBeInstanceOf(SpatieActivityLogger::class);

        $spatieQuery = app(ActivityQueryContract::class);
        expect($spatieQuery)->toBeInstanceOf(SpatieActivityQuery::class);
    }

    // None driver
    app()->instance(ActivityRuntime::class, new ActivityRuntime('none', 'none', MoonTrailActivity::class, false, false));
    app()->forgetInstance(ActivityLoggerContract::class);
    app()->forgetInstance(ActivityQueryContract::class);
    $nullLogger = app(ActivityLoggerContract::class);
    expect($nullLogger)->toBeInstanceOf(NullActivityLogger::class);

    $nullQuery = app(ActivityQueryContract::class);
    expect($nullQuery)->toBeInstanceOf(NullActivityQuery::class);

    // Reset back
    app()->forgetInstance(ActivityRuntime::class);
    app()->forgetInstance(ActivityLoggerContract::class);
    app()->forgetInstance(ActivityQueryContract::class);
});

it('tests ModelVersion activity resolver callback set by provider', function (): void {
    $post = TestPost::query()->create(['name' => 'Provider Version Test']);
    /** @var ModelVersion $version */
    $version = app(VersionManagerContract::class)->createVersion($post, 'created', 1);

    expect($version)->not->toBeNull();
    $resolved = $version->activity;
    expect($resolved)->not->toBeNull();
});

it('tests core contracts resolved from container', function (): void {
    expect(app(VersionManagerContract::class))->not->toBeNull()
        ->and(app(DiffRendererContract::class))->not->toBeNull()
        ->and(app(RollbackStrategyContract::class))->not->toBeNull();
});
