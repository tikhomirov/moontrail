<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Tests\Fixtures\GuardedPost;
use MoonShine\MoonTrail\Versioning\RollbackService;
use MoonShine\MoonTrail\Versioning\VersionManager;

beforeEach(function (): void {
    config()->set('moontrail.tracking.versions.enabled', false);
});

it('throws no changes exception for guarded model with empty rollback payload', function (): void {
    // Force-create since $fillable is empty
    $post = new GuardedPost;
    $post->forceFill(['name' => 'Original', 'body' => 'Body']);
    $post->save();

    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    $post->forceFill(['name' => 'Changed']);
    $post->save();
    $manager->createVersion($post, 'updated');

    $service = app(RollbackService::class);

    expect(static fn () => $service->rollback($post, 1))
        ->toThrow(\MoonShine\MoonTrail\Exceptions\NoChangesToRollbackException::class);
});
