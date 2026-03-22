<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

beforeEach(function (): void {
    config()->set('moontrail.activity_logger', 'database');
    app()->forgetInstance(ActivityLoggerContract::class);
    app()->forgetInstance(ActivityQueryContract::class);
});

it('supports database logger end-to-end for read path', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Before',
        'body' => 'Body',
    ]);

    $post->update(['name' => 'After']);

    $activity = MoonTrailActivity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    expect($activity->log_name)->toBe('default')
        ->and($activity->subject_type)->toBe($post->getMorphClass())
        ->and($activity->subject_id)->toBe($post->getKey())
        ->and($activity->properties)->toBeArray();

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk()
        ->assertJsonPath('event', $activity->event);
});

it('resolves moontrail resource model to native activity model in database mode', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->getModel())->toBeInstanceOf(MoonTrailActivity::class);
});
