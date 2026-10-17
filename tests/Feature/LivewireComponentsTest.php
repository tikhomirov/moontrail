<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use MoonShine\MoonTrail\Adapters\Livewire\Components\ActivityDetailModal;
use MoonShine\MoonTrail\Adapters\Livewire\Components\ActivityDiffModal;
use MoonShine\MoonTrail\Adapters\Livewire\Components\ActivityIndex;
use MoonShine\MoonTrail\Adapters\Livewire\Components\ActivityTimeline;
use MoonShine\MoonTrail\Adapters\Livewire\Components\ActivityTimelineModal;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Tests\Fixtures\AllowRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    config()->set('moontrail.ui_adapter', 'livewire');
    View::replaceNamespace('moontrail', __DIR__ . '/../../resources/views');

    \Livewire\Livewire::component('moontrail-timeline', ActivityTimeline::class);
    \Livewire\Livewire::component('moontrail-timeline-modal', ActivityTimelineModal::class);
    \Livewire\Livewire::component('moontrail-diff-modal', ActivityDiffModal::class);
    \Livewire\Livewire::component('moontrail-detail-modal', ActivityDetailModal::class);
    \Livewire\Livewire::component('moontrail-index', ActivityIndex::class);
});

it('renders livewire activity index with kpi cards and table', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $post = TestPost::query()->create(['name' => 'Post A']);

    Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'Created Post A',
        'subject_type' => $post->getMorphClass(),
        'subject_id'   => $post->getKey(),
        'causer_type'  => $admin->getMorphClass(),
        'causer_id'    => $admin->getKey(),
        'event'        => 'created',
        'properties'   => ['attributes' => ['name' => 'Post A']],
    ]);

    Livewire::actingAs($admin)
        ->test(ActivityIndex::class)
        ->assertStatus(200)
        ->assertSee('Post A')
        ->assertSee('Created Post A')
        ->assertSee('Diff');
});

it('can filter activities by search and event in activity index', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $post1 = TestPost::query()->create(['name' => 'Alpha']);
    $post2 = TestPost::query()->create(['name' => 'Beta']);

    Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'Alpha record',
        'subject_type' => $post1->getMorphClass(),
        'subject_id'   => $post1->getKey(),
        'event'        => 'created',
        'properties'   => ['attributes' => ['name' => 'Alpha']],
    ]);

    Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'Beta record',
        'subject_type' => $post2->getMorphClass(),
        'subject_id'   => $post2->getKey(),
        'event'        => 'deleted',
        'properties'   => ['old' => ['name' => 'Beta']],
    ]);

    Livewire::actingAs($admin)
        ->test(ActivityIndex::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha record')
        ->assertDontSee('Beta record')
        ->set('search', '')
        ->set('event', 'deleted')
        ->assertSee('Beta record')
        ->assertDontSee('Alpha record');
});

it('opens diff modal on event and checks permissions', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $post = TestPost::query()->create(['name' => 'Original Post']);

    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $activity = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'Updated Post',
        'subject_type' => $post->getMorphClass(),
        'subject_id'   => $post->getKey(),
        'event'        => 'updated',
        'properties'   => [
            'old'        => ['name' => 'Original Post'],
            'attributes' => ['name' => 'New Name'],
        ],
    ]);

    Livewire::actingAs($admin)
        ->test(ActivityDiffModal::class)
        ->assertSet('isOpen', false)
        ->dispatch('open-moontrail-diff', activityId: $activity->id)
        ->assertSet('isOpen', true)
        ->assertSet('activityId', $activity->id)
        ->assertSee('Original Post')
        ->assertSee('New Name')
        ->call('close')
        ->assertSet('isOpen', false);
});

it('opens detail modal with 3 tabs', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $post = TestPost::query()->create(['name' => 'Detail Target']);

    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $activity = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'Created Detail Target',
        'subject_type' => $post->getMorphClass(),
        'subject_id'   => $post->getKey(),
        'event'        => 'created',
        'properties'   => ['attributes' => ['name' => 'Detail Target']],
    ]);

    Livewire::actingAs($admin)
        ->test(ActivityDetailModal::class)
        ->dispatch('open-moontrail-detail', activityId: $activity->id)
        ->assertSet('isOpen', true)
        ->assertSee((string) __('moontrail::ui.section_general'))
        ->call('setTab', 'relations')
        ->assertSet('activeTab', 'relations')
        ->call('setTab', 'changes')
        ->assertSet('activeTab', 'changes')
        ->call('close')
        ->assertSet('isOpen', false);
});

it('opens timeline modal and renders history', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $post = TestPost::query()->create(['name' => 'Timeline Post']);

    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    Livewire::actingAs($admin)
        ->test(ActivityTimelineModal::class)
        ->assertSet('isOpen', false)
        ->dispatch('open-moontrail-timeline', modelClass: $post->getMorphClass(), modelId: $post->getKey(), title: 'Test History')
        ->assertSet('isOpen', true)
        ->assertSee('Test History')
        ->call('close')
        ->assertSet('isOpen', false);
});

it('prompts rollback and cancels in activity timeline', function (): void {
    $admin = TestAdmin::query()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $post = TestPost::query()->create(['name' => 'Rollback Post V1']);
    $post->update(['name' => 'Rollback Post V2']);

    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $v1 = ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->where('version', 1)
        ->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ActivityTimeline::class, ['model' => $post])
        ->call('selectVersion', $v1->id)
        ->assertSet('selectedVersionId', $v1->id)
        ->assertSet('confirmingRollback', false)
        ->call('promptRollback')
        ->assertSet('confirmingRollback', true)
        ->assertSee((string) __('moontrail::ui.rollback_confirm_title'))
        ->call('cancelRollback')
        ->assertSet('confirmingRollback', false);
});
