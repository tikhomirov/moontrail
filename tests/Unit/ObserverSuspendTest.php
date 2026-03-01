<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\MoonTrailObserver;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

beforeEach(function (): void {
    config()->set('moontrail.versioning.enabled', false);
    MoonTrailObserver::resume();
});

afterEach(function (): void {
    MoonTrailObserver::resume();
});

it('does not create versions when observer is suspended', function (): void {
    config()->set('moontrail.versioning.enabled', true);

    $post = TestPost::query()->create(['name' => 'Initial']);
    $initialCount = ModelVersion::query()->forModel($post)->count();

    MoonTrailObserver::suspend();

    $post->update(['name' => 'Changed']);

    expect(ModelVersion::query()->forModel($post)->count())->toBe($initialCount);
});

it('resumes version creation after resume', function (): void {
    config()->set('moontrail.versioning.enabled', true);

    $post = TestPost::query()->create(['name' => 'Initial']);

    MoonTrailObserver::suspend();
    $post->update(['name' => 'Suspended Change']);

    $countAfterSuspend = ModelVersion::query()->forModel($post)->count();

    MoonTrailObserver::resume();
    $post->update(['name' => 'Resumed Change']);

    expect(ModelVersion::query()->forModel($post)->count())->toBeGreaterThan($countAfterSuspend);
});

it('reports suspended state correctly', function (): void {
    expect(MoonTrailObserver::isSuspended())->toBeFalse();

    MoonTrailObserver::suspend();
    expect(MoonTrailObserver::isSuspended())->toBeTrue();

    MoonTrailObserver::resume();
    expect(MoonTrailObserver::isSuspended())->toBeFalse();
});
