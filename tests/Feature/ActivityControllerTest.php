<?php

declare(strict_types=1);

use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use Spatie\Activitylog\Models\Activity;

it('returns diff html for activity record when authenticated', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Old',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'New',
    ]);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Diff View',
        'email' => 'diff-view@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk()
        ->assertJsonStructure([
            'html',
            'event',
        ])
        ->assertJsonPath('event', $activity->event);
});

it('returns 403 when diff is requested without authentication', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Old',
        'body' => 'Body',
    ]);

    $post->update(['name' => 'New']);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertForbidden();
});

it('returns 404 for missing activity diff endpoint', function (): void {
    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Diff 404',
        'email' => 'diff-404@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => 999999]))
        ->assertNotFound();
});

it('redirects guest to login on diff route with middleware enabled', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Old',
        'body' => 'Body',
    ]);

    $post->update(['name' => 'New']);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $this->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertRedirect('/admin/login');
});

it('allows authenticated moonshine user to access diff route with middleware enabled', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Old',
        'body' => 'Body',
    ]);

    $post->update(['name' => 'New']);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Admin Middleware Diff',
        'email' => 'admin-middleware-diff@example.test',
    ]);

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk();
});
