<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Exceptions\VersionLimitExceededException;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\VersionManager;

it('overflow prevent throws before insert and keeps versions count unchanged', function (): void {
    config()->set('moontrail.tracking.versions.enabled', false);
    config()->set('moontrail.tracking.versions.limit', 1);
    config()->set('moontrail.tracking.versions.on_limit', 'prevent');

    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    expect($post->versions()->count())->toBe(1);

    expect(static fn () => $manager->createVersion($post, 'updated'))
        ->toThrow(VersionLimitExceededException::class);

    expect($post->versions()->count())->toBe(1);
});
