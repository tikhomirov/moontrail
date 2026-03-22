<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Logging\DatabaseActivityLogger;
use MoonShine\MoonTrail\Logging\NullActivityLogger;
use MoonShine\MoonTrail\Logging\SpatieActivityLogger;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    config()->set('moontrail.versioning.enabled', false);
});

it('resolves spatie logger when driver is spatie', function (): void {
    config()->set('moontrail.activity_logger', 'spatie');
    app()->forgetInstance(ActivityLoggerContract::class);

    $logger = app(ActivityLoggerContract::class);

    expect($logger)->toBeInstanceOf(SpatieActivityLogger::class);
});

it('resolves database logger when driver is database', function (): void {
    config()->set('moontrail.activity_logger', 'database');

    app()->forgetInstance(ActivityLoggerContract::class);

    $logger = app(ActivityLoggerContract::class);

    expect($logger)->toBeInstanceOf(DatabaseActivityLogger::class);
});

it('resolves null logger when driver is none', function (): void {
    config()->set('moontrail.activity_logger', 'none');

    app()->forgetInstance(ActivityLoggerContract::class);

    $logger = app(ActivityLoggerContract::class);

    expect($logger)->toBeInstanceOf(NullActivityLogger::class);
});

it('spatie logger creates activity record', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);

    $logger = new SpatieActivityLogger;
    $id = $logger->log($post, 'created', [
        'old'        => [],
        'attributes' => ['name' => 'Test'],
    ]);

    expect($id)->toBeInt()
        ->and(Activity::query()->find($id))->not->toBeNull();
});

it('database logger creates native activity record', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);

    $logger = new DatabaseActivityLogger;
    $id = $logger->log($post, 'created', [
        'old'        => [],
        'attributes' => ['name' => 'Test'],
    ]);

    expect($id)->toBeInt();

    $record = MoonTrailActivity::query()->find($id);
    expect($record)->not->toBeNull()
        ->and($record->event)->toBe('created')
        ->and($record->model_type)->toBe($post->getMorphClass())
        ->and($record->properties)->toBeArray()
        ->and($record->properties['attributes']['name'])->toBe('Test');
});

it('null logger returns null and creates no records', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);

    $logger = new NullActivityLogger;
    $id = $logger->log($post, 'created');

    expect($id)->toBeNull();
});
