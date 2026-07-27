<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\MoonTrailObserver;
use MoonShine\MoonTrail\Tests\Fixtures\TestAutoTrackedPost;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    // Register observer manually — as ServiceProvider does for auto_track_models
    TestAutoTrackedPost::observe(app(MoonTrailObserver::class));
});

it('auto-observer writes to activity_log when log_to_activity is enabled', function (): void {
    config(['moontrail.auto_track.log_to_activity' => true]);

    $post = TestAutoTrackedPost::query()->create([
        'name' => 'Auto',
        'body' => 'Body',
    ]);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created');
});

it('auto-observer writes old and attributes into activity properties', function (): void {
    config(['moontrail.auto_track.log_to_activity' => true]);

    $post = TestAutoTrackedPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update(['name' => 'Modified']);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $old = data_get($activity->properties, 'old', []);
    $attributes = data_get($activity->properties, 'attributes', []);

    expect($old)->toBeArray()
        ->and($attributes)->toBeArray()
        ->and(data_get($old, 'name'))->toBe('Original')
        ->and(data_get($attributes, 'name'))->toBe('Modified');
});

it('auto-observer links activity_id in model_version', function (): void {
    config(['moontrail.auto_track.log_to_activity' => true]);

    $post = TestAutoTrackedPost::query()->create([
        'name' => 'Link test',
        'body' => 'Body',
    ]);

    $version = ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->latest('version')
        ->first();

    expect($version)->not->toBeNull()
        ->and($version->activity_id)->not->toBeNull();

    $activity = Activity::query()->find($version->activity_id);
    expect($activity)->not->toBeNull();
});

it('auto-observer does not write to activity_log when log_to_activity is disabled', function (): void {
    config(['moontrail.auto_track.log_to_activity' => false]);

    $post = TestAutoTrackedPost::query()->create([
        'name' => 'No log',
        'body' => 'Body',
    ]);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->first();

    expect($activity)->toBeNull();
});

it('auto-observer creates model_version regardless of log_to_activity setting', function (): void {
    config(['moontrail.auto_track.log_to_activity' => false]);

    $post = TestAutoTrackedPost::query()->create([
        'name' => 'Version only',
        'body' => 'Body',
    ]);

    $version = ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->first();

    expect($version)->not->toBeNull()
        ->and($version->event)->toBe('created');
});
