<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\RollbackService;
use MoonShine\MoonTrail\Versioning\VersionManager;

it('rollback succeeds without rules when validation mode is if_rules_provided (default)', function (): void {
    config(['moontrail.rollback.validate' => true]);
    config(['moontrail.rollback.require_rules' => false]);

    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    // Should not throw
    app(RollbackService::class)->rollback($post, 1);

    $post->refresh();
    expect($post->name)->toBe('Original');
});

it('rollback applies rules when provided and passes', function (): void {
    config(['moontrail.rollback.validate' => true]);
    config(['moontrail.rollback.require_rules' => false]);

    $post = TestPost::query()->create(['name' => 'Valid name']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    app(RollbackService::class)->rollback($post, 1, rules: ['name' => 'required|string']);

    $post->refresh();
    expect($post->name)->toBe('Valid name');
});

it('rollback fails with ValidationException when rules fail', function (): void {
    config(['moontrail.rollback.validate' => true]);
    config(['moontrail.rollback.require_rules' => false]);

    $post = TestPost::query()->create(['name' => 'x']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    expect(fn () => app(RollbackService::class)->rollback($post, 1, rules: ['name' => 'min:100']))
        ->toThrow(ValidationException::class);
});

it('rollback succeeds with require_rules=true and no rules (empty validation passes)', function (): void {
    config([
        'moontrail.rollback.validate'      => true,
        'moontrail.rollback.require_rules' => true,
    ]);

    $post = TestPost::query()->create(['name' => 'Original']);
    $manager = app(VersionManager::class);
    $manager->createVersion($post, 'created');

    // With validation=required and no rules, validation runs with empty rules and passes
    app(RollbackService::class)->rollback($post, 1);

    $post->refresh();
    expect($post->name)->toBe('Original');
});
