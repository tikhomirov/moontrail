<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\RollbackService;
use MoonShine\MoonTrail\Versioning\VersionManager;

it('rolls back model to target version', function (): void {
    $post = TestPost::query()->create(['name' => 'Original', 'body' => 'Body']);

    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    $post->update(['name' => 'Changed']);
    $manager->createVersion($post, 'updated');

    $service = app(RollbackService::class);
    $service->rollback($post, 1);

    $post->refresh();

    expect($post->name)->toBe('Original');
});

it('throws ModelVersionNotFoundException for non-existent version', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);

    $service = app(RollbackService::class);

    expect(fn () => $service->rollback($post, 99))
        ->toThrow(\MoonShine\MoonTrail\Exceptions\ModelVersionNotFoundException::class);
});

it('creates a rollback version with is_rollback=true after rollback', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');
    $post->update(['name' => 'Changed']);
    $manager->createVersion($post, 'updated');

    $service = app(RollbackService::class);
    $service->rollback($post, 1);

    $rollbackVersion = $post->versions()->first();

    expect($rollbackVersion->is_rollback)->toBeTrue()
        ->and($rollbackVersion->rollback_to_version)->toBe(1);
});

it('rollback restores all fillable fields', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Original Name',
        'body' => 'Original Body',
    ]);

    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    $post->update(['name' => 'New Name', 'body' => 'New Body']);
    $manager->createVersion($post, 'updated');

    app(RollbackService::class)->rollback($post, 1);
    $post->refresh();

    expect($post->name)->toBe('Original Name')
        ->and($post->body)->toBe('Original Body');
});

it('validates payload against provided rules and throws on failure', function (): void {
    $post = TestPost::query()->create(['name' => 'x']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');

    $service = app(RollbackService::class);

    expect(fn () => $service->rollback($post, 1, rules: ['name' => 'min:100']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('rolls back and restores soft-deleted model', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Original Name',
        'body' => 'Original Body',
    ]);

    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    $post->update([
        'name' => 'Changed Name',
        'body' => 'Changed Body',
    ]);
    $manager->createVersion($post, 'updated');

    $post->delete();

    app(RollbackService::class)->rollback($post, 1);

    $restored = TestPost::query()->find($post->getKey());

    expect($restored)->not->toBeNull()
        ->and($restored?->trashed())->toBeFalse()
        ->and($restored?->name)->toBe('Original Name')
        ->and($restored?->versions()->first()?->is_rollback)->toBeTrue();
});
