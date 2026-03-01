<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Exceptions\RollbackDeniedException;
use MoonShine\MoonTrail\Tests\Fixtures\AllowedRollbackPost;
use MoonShine\MoonTrail\Tests\Fixtures\AllowRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\DeniedRollbackPost;
use MoonShine\MoonTrail\Tests\Fixtures\DenyRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\RollbackAuthorizationResolver;

// ---------------------------------------------------------------------------
// canRollback — non-throwing checks
// ---------------------------------------------------------------------------

it('returns false from canRollback when guard is not authenticated', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect($resolver->canRollback($post))->toBeFalse();
});

it('returns true from canRollback when policy allows', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $admin = TestAdmin::query()->create(['name' => 'A', 'email' => 'rar-allow@example.test']);
    MoonShineAuth::getGuard()->setUser($admin);

    $post = TestPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect($resolver->canRollback($post))->toBeTrue();
});

it('returns false from canRollback when policy denies', function (): void {
    Gate::policy(TestPost::class, DenyRollbackPolicy::class);

    $admin = TestAdmin::query()->create(['name' => 'A', 'email' => 'rar-deny@example.test']);
    MoonShineAuth::getGuard()->setUser($admin);

    $post = TestPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect($resolver->canRollback($post))->toBeFalse();
});

it('returns true from canRollback when no policy and model allows', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'A', 'email' => 'rar-model-allow@example.test']);
    MoonShineAuth::getGuard()->setUser($admin);

    $post = AllowedRollbackPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect($resolver->canRollback($post))->toBeTrue();
});

it('returns false from canRollback when no policy and model denies', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'A', 'email' => 'rar-model-deny@example.test']);
    MoonShineAuth::getGuard()->setUser($admin);

    $post = DeniedRollbackPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect($resolver->canRollback($post))->toBeFalse();
});

// ---------------------------------------------------------------------------
// authorize — throwing checks
// ---------------------------------------------------------------------------

it('throws AuthorizationException from authorize when not authenticated', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect(fn () => $resolver->authorize($post))
        ->toThrow(AuthorizationException::class);
});

it('throws RollbackDeniedException from authorize when model denies and no policy', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'A', 'email' => 'rar-auth-deny@example.test']);
    MoonShineAuth::getGuard()->setUser($admin);

    $post = DeniedRollbackPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    expect(fn () => $resolver->authorize($post))
        ->toThrow(RollbackDeniedException::class);
});

it('does not throw from authorize when model allows rollback', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'A', 'email' => 'rar-auth-allow@example.test']);
    MoonShineAuth::getGuard()->setUser($admin);

    $post = AllowedRollbackPost::query()->create(['name' => 'Test']);
    $resolver = app(RollbackAuthorizationResolver::class);

    // Should not throw
    $resolver->authorize($post);

    expect(true)->toBeTrue();
});
