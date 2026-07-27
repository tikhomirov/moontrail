<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Enums\ChangeType;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\VersionManager;

beforeEach(function (): void {
    config()->set('moontrail.versioning.enabled', false);
    config()->set('moontrail.ui.hidden_fields', []);
});

it('creates first version with number 1', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $manager = app(VersionManager::class);
    $version = $manager->createVersion($post, 'created');

    expect($version->version)->toBe(1)
        ->and($version->event)->toBe('created');
});

it('increments version number', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');

    $post->update(['name' => 'Updated']);
    $v2 = $manager->createVersion($post, 'updated');

    expect($v2->version)->toBe(2);
});

it('computes diff between two versions', function (): void {
    $post = TestPost::query()->create(['name' => 'Old', 'email' => 'same@mail.com']);
    $manager = app(VersionManager::class);

    $v1 = $manager->createVersion($post, 'created');

    $post->update(['name' => 'New']);
    $v2 = $manager->createVersion($post, 'updated');

    $diff = $manager->diff($v1, $v2);

    expect($diff['name']->type)->toBe(ChangeType::Modified)
        ->and($diff['name']->oldValue)->toBe('Old')
        ->and($diff['name']->newValue)->toBe('New');
});

it('getVersion returns correct version or null', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');

    expect($manager->getVersion($post, 1))->not->toBeNull()
        ->and($manager->getVersion($post, 99))->toBeNull();
});

it('snapshot excludes version-excluded fields', function (): void {
    $post = TestPost::query()->create(['name' => 'Test', 'password' => 'secret']);
    $manager = app(VersionManager::class);

    $version = $manager->createVersion($post, 'created');

    expect($version->snapshot)->not->toHaveKey('password');
});

it('diffWithCurrent compares version snapshot to current model', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);

    $v1 = $manager->createVersion($post, 'created');
    $post->update(['name' => 'Changed']);
    $post->refresh();

    $diff = $manager->diffWithCurrent($v1, $post);

    expect($diff['name']->type)->toBe(ChangeType::Modified)
        ->and($diff['name']->oldValue)->toBe('Original')
        ->and($diff['name']->newValue)->toBe('Changed');
});

it('enforces max_versions by deleting oldest', function (): void {
    config()->set('moontrail.versioning.max_versions', 3);
    config()->set('moontrail.versioning.overflow_strategy', 'delete_oldest');

    $post = TestPost::query()->create(['name' => 'v0']);
    $manager = app(VersionManager::class);

    foreach (range(1, 5) as $i) {
        $post->update(['name' => "v{$i}"]);
        $manager->createVersion($post, 'updated');
    }

    $remaining = $post->versions()->count();

    expect($remaining)->toBeLessThanOrEqual(3);
});

it('throws when strategy is prevent and limit is reached', function (): void {
    config()->set('moontrail.versioning.max_versions', 1);
    config()->set('moontrail.versioning.overflow_strategy', 'prevent');

    $post = TestPost::query()->create(['name' => 'v1']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');

    expect(static fn () => $manager->createVersion($post, 'updated'))
        ->toThrow(\MoonShine\MoonTrail\Exceptions\VersionLimitExceededException::class);

    expect($post->versions()->count())->toBe(1);
});

it('stores is_rollback flag as false by default', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $manager = app(VersionManager::class);

    $version = $manager->createVersion($post, 'created');

    expect($version->is_rollback)->toBeFalse();
});
