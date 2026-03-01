<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use MoonShine\MoonTrail\Events\ModelRolledBack;
use MoonShine\MoonTrail\Events\ModelRollingBack;
use MoonShine\MoonTrail\Exceptions\RollbackCancelledException;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\RollbackService;
use MoonShine\MoonTrail\Versioning\VersionManager;

it('dispatches ModelRollingBack before any DB change', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');
    $post->update(['name' => 'Changed']);
    $manager->createVersion($post, 'updated');

    $fired = false;
    Event::listen(ModelRollingBack::class, function (ModelRollingBack $event) use (&$fired): void {
        $fired = true;
        expect($event->targetVersion)->toBe(1)
            ->and($event->model->getKey())->toBe((int) $event->model->getKey());
    });

    app(RollbackService::class)->rollback($post, 1);

    expect($fired)->toBeTrue();
});

it('dispatches ModelRolledBack after successful rollback with correct version data', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');
    $post->update(['name' => 'Changed']);
    $manager->createVersion($post, 'updated');

    $firedData = null;
    Event::listen(ModelRolledBack::class, function (ModelRolledBack $event) use (&$firedData): void {
        $firedData = [
            'from'  => $event->fromVersion,
            'to'    => $event->toVersion,
            'new'   => $event->newVersion->is_rollback,
            'newTo' => $event->newVersion->rollback_to_version,
        ];
    });

    app(RollbackService::class)->rollback($post, 1);

    expect($firedData)->not->toBeNull()
        ->and($firedData['to'])->toBe(1)
        ->and($firedData['new'])->toBeTrue()
        ->and($firedData['newTo'])->toBe(1);
});

it('throws RollbackCancelledException and does not change data when listener returns false', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');
    $post->update(['name' => 'Changed']);
    $manager->createVersion($post, 'updated');

    $versionCountBefore = $post->versions()->count();

    Event::listen(ModelRollingBack::class, static fn (): bool => false);

    expect(fn () => app(RollbackService::class)->rollback($post, 1))
        ->toThrow(RollbackCancelledException::class);

    // Model must be unchanged
    $post->refresh();
    expect($post->name)->toBe('Changed')
        ->and($post->versions()->count())->toBe($versionCountBefore);
});

it('does not dispatch ModelRolledBack when rollback is cancelled', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');
    $post->update(['name' => 'Changed']);
    $manager->createVersion($post, 'updated');

    $rolledBackFired = false;

    Event::listen(ModelRollingBack::class, static fn (): bool => false);
    Event::listen(ModelRolledBack::class, static function () use (&$rolledBackFired): void {
        $rolledBackFired = true;
    });

    try {
        app(RollbackService::class)->rollback($post, 1);
    } catch (RollbackCancelledException) {
        // expected
    }

    expect($rolledBackFired)->toBeFalse();
});
