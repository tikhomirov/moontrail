<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Tests\Fixtures\AllowedRollbackPost;
use MoonShine\MoonTrail\Tests\Fixtures\AllowRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\DeniedRollbackPost;
use MoonShine\MoonTrail\Tests\Fixtures\DenyRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('returns 403 when rollback is requested without moonshine authentication', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $this->withoutMiddleware()
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertForbidden();
});

it('returns 403 when rollback policy denies access', function (): void {
    Gate::policy(TestPost::class, DenyRollbackPolicy::class);

    $post = TestPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Deny Policy',
        'email' => 'deny-policy@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertForbidden();
});

it('returns 403 when no policy exists and model does not explicitly allow rollback', function (): void {
    $post = DeniedRollbackPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Deny Model',
        'email' => 'deny-model@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertForbidden();
});

it('allows rollback when policy allows and creates rollback history version', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $post = TestPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Modified',
        'body' => 'Changed',
    ]);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Allow Policy',
        'email' => 'allow-policy@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->from('/admin/test')
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertRedirect('/admin/test')
        ->assertSessionHas('toast.message', __('moontrail::ui.rollback_success'));

    $post->refresh();

    expect($post->name)->toBe('Original')
        ->and((bool) $post->versions()->first()?->is_rollback)->toBeTrue()
        ->and((int) $post->versions()->firstOrFail()->rollback_to_version)->toBe(1);
});

it('returns 422 for missing target version id', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Validation',
        'email' => 'validation@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => 999999,
        ])
        ->assertStatus(422)
        ->assertSessionHas('toast.message', __('moontrail::ui.rollback_error_validation'));
});

it('restores soft-deleted model when rollback is requested', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $post = TestPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $post->delete();

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Soft Delete Rollback',
        'email' => 'soft-delete-rollback@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->from('/admin/test')
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertRedirect('/admin/test')
        ->assertSessionHas('toast.message', __('moontrail::ui.rollback_success'));

    $restored = TestPost::query()->find($post->getKey());

    expect($restored)->not->toBeNull()
        ->and($restored?->trashed())->toBeFalse()
        ->and($restored?->name)->toBe('Original')
        ->and((bool) $restored?->versions()->first()?->is_rollback)->toBeTrue()
        ->and((int) $restored?->versions()->firstOrFail()->rollback_to_version)->toBe(1);
});

it('redirects guest to login on rollback route with middleware enabled', function (): void {
    $post = AllowedRollbackPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $this->post(route('moonshine.moontrail.rollback'), [
        'modelVersion' => $target->id,
    ])->assertRedirect('/admin/login');
});

it('allows authenticated user on rollback route with middleware enabled', function (): void {
    $post = AllowedRollbackPost::query()->create([
        'name' => 'Original',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Changed',
        'body' => 'Changed body',
    ]);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Middleware Rollback',
        'email' => 'admin-middleware-rollback@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->from('/admin/test')
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertRedirect('/admin/test')
        ->assertSessionHas('toast.message', __('moontrail::ui.rollback_success'));

    $post->refresh();

    expect($post->name)->toBe('Original');
});
