<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Components\ActivityTimeline;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Tests\Fixtures\AllowRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Tests\Fixtures\TestPostResource;

beforeEach(function (): void {
    View::replaceNamespace('moontrail', __DIR__ . '/../../resources/views');
});

it('renders activity timeline component', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Test',
    ]);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource)->limit(5);
    $html = (string) $component->render();

    expect($html)->toContain('History');
});

it('renders timeline with preset versions (standalone mode)', function (): void {
    $post = TestPost::query()->create(['name' => 'Original']);
    $post->update(['name' => 'Updated']);

    $versions = ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->with('author')
        ->latest('version')
        ->get()
        ->all();

    $component = ActivityTimeline::make('Version History')->setVersions($versions);
    $html = (string) $component->render();

    expect($html)
        ->toContain('Version History')
        ->toContain('Version #');
});

it('renders show diff button when activity_id is present', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->toContain(__('moontrail::ui.show_diff'));
});

it('does not render show diff button when activity_id is missing', function (): void {
    $post = TestPost::query()->create(['name' => 'Standalone']);

    $version = new ModelVersion([
        'versionable_type'    => $post->getMorphClass(),
        'versionable_id'      => $post->getKey(),
        'version'             => 1,
        'snapshot'            => ['name' => 'Standalone'],
        'activity_id'         => null,
        'event'               => 'created',
        'is_rollback'         => false,
        'rollback_to_version' => null,
    ]);

    $component = ActivityTimeline::make('History')->setVersions([$version]);
    $html = (string) $component->render();

    expect($html)->not->toContain(__('moontrail::ui.show_diff'));
});

it('does not render rollback button when canRollback is false', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->not->toContain(__('moontrail::ui.rollback_to_version', ['version' => 1]))
        ->and($html)->not->toContain(__('moontrail::ui.rollback_confirm_title'));
});

it('hides rollback button when withoutRollback is called', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource)->withoutRollback();
    $html = (string) $component->render();

    expect($html)->not->toContain(__('moontrail::ui.rollback'));
});

it('renders rollback button for all versions except first when canRollback is true', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $admin = TestAdmin::query()->create([
        'name'  => 'Timeline Admin 1',
        'email' => 'timeline-admin-1@example.test',
    ]);

    $guard = MoonShineAuth::getGuard();
    $guard->setUser($admin);

    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed once']);
    $post->update(['name' => 'Changed twice']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)
        ->toContain(__('moontrail::ui.rollback_to_version', ['version' => 3]))
        ->toContain(__('moontrail::ui.rollback_to_version', ['version' => 2]))
        ->not->toContain(__('moontrail::ui.rollback_to_version', ['version' => 1]))
        ->toContain(__('moontrail::ui.rollback_confirm_title'));
});

it('hides rollback ui when withoutRollback is called even if model allows rollback', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $admin = TestAdmin::query()->create([
        'name'  => 'Timeline Admin 2',
        'email' => 'timeline-admin-2@example.test',
    ]);

    $guard = MoonShineAuth::getGuard();
    $guard->setUser($admin);

    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource)->withoutRollback();
    $html = (string) $component->render();

    expect($html)
        ->not->toContain(__('moontrail::ui.rollback_to_version', ['version' => 1]))
        ->not->toContain(__('moontrail::ui.rollback_confirm_title'));
});

it('renders event color badges for created and updated events', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)
        ->toContain('bg-green-500')
        ->toContain('bg-blue-500');
});

it('renders no_history message when no versions exist', function (): void {
    $component = ActivityTimeline::make('History')->setVersions([]);
    $html = (string) $component->render();

    expect($html)->toContain(__('moontrail::ui.no_history'));
});

it('shows no-rights hint for non-earliest versions when canRollback is false and showRollback is true', function (): void {
    // Default TestPost: isRollbackAllowed() = false, no authenticated user.
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed once']);
    $post->update(['name' => 'Changed twice']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    // The hint text (tooltip) should be present for non-earliest versions.
    expect($html)->toContain(__('moontrail::ui.rollback_denied_hint'))
        ->toContain(__('moontrail::ui.rollback_no_rights'));
});

it('does not show no-rights hint when withoutRollback is called', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource)->withoutRollback();
    $html = (string) $component->render();

    expect($html)->not->toContain(__('moontrail::ui.rollback_denied_hint'));
});

it('does not show no-rights hint for the earliest version', function (): void {
    // Even with showRollbackHint=true the earliest version never gets the hint.
    $post = TestPost::query()->create(['name' => 'Test']);
    $post->update(['name' => 'Changed']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    // With only 2 versions the earliest is version 1. The hint must appear only on version 2.
    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    // rollback_to_version for v1 must NOT appear (it's the earliest).
    expect($html)->not->toContain(__('moontrail::ui.rollback_to_version', ['version' => 1]));
});
