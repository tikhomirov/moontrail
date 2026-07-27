<?php

declare(strict_types=1);

use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\MoonTrail\Support\MoonTrailMenuItem;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

// ---------------------------------------------------------------------------
// enabled
// ---------------------------------------------------------------------------

it('returns null when menu.enabled is false', function (): void {
    config()->set('moontrail.menu.enabled', false);

    expect(MoonTrailMenuItem::make())->toBeNull();
});

// ---------------------------------------------------------------------------
// label
// ---------------------------------------------------------------------------

it('accepts a custom label via argument', function (): void {
    config()->set('moontrail.tracked_models', []);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);

    $item = MoonTrailMenuItem::make('Custom Label');

    expect($item)->toBeInstanceOf(MenuItem::class);
});

it('uses menu.label config when no argument given', function (): void {
    config()->set('moontrail.tracked_models', []);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.label', 'Config Label');
    config()->set('moontrail.menu.show_all_item', true);

    $item = MoonTrailMenuItem::make();

    expect($item)->toBeInstanceOf(MenuItem::class);
});

// ---------------------------------------------------------------------------
// show_children = false
// ---------------------------------------------------------------------------

it('returns a single MenuItem when show_children is false (ignores models)', function (): void {
    config()->set('moontrail.tracked_models', [TestPost::class]);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', false);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuItem::class);
});

// ---------------------------------------------------------------------------
// no tracked models
// ---------------------------------------------------------------------------

it('returns a single MenuItem when no tracked models and show_all_item is true', function (): void {
    config()->set('moontrail.tracked_models', []);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', true);
    config()->set('moontrail.menu.show_all_item', true);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuItem::class);
});

it('returns null when no tracked models and show_all_item is false', function (): void {
    config()->set('moontrail.tracked_models', []);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', true);
    config()->set('moontrail.menu.show_all_item', false);

    expect(MoonTrailMenuItem::make())->toBeNull();
});

// ---------------------------------------------------------------------------
// tracked models present
// ---------------------------------------------------------------------------

it('returns a MenuGroup when tracked models are configured', function (): void {
    config()->set('moontrail.tracked_models', [TestPost::class]);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', true);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuGroup::class);
});

it('creates sub-items for each tracked model plus All item', function (): void {
    config()->set('moontrail.tracked_models', [TestPost::class]);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', true);
    config()->set('moontrail.menu.show_all_item', true);
    config()->set('moontrail.menu.exclude_models', []);

    /** @var MenuGroup $group */
    $group = MoonTrailMenuItem::make();

    expect($group)->toBeInstanceOf(MenuGroup::class)
        ->and($group->getItems())->toHaveCount(2);
});

it('hides All item when show_all_item is false', function (): void {
    config()->set('moontrail.tracked_models', [TestPost::class]);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', true);
    config()->set('moontrail.menu.show_all_item', false);
    config()->set('moontrail.menu.exclude_models', []);

    /** @var MenuGroup $group */
    $group = MoonTrailMenuItem::make();

    expect($group->getItems())->toHaveCount(1);
});

it('excludes models listed in menu.exclude_models', function (): void {
    config()->set('moontrail.tracked_models', [TestPost::class]);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.show_children', true);
    config()->set('moontrail.menu.show_all_item', true);
    config()->set('moontrail.menu.exclude_models', [TestPost::class]);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuItem::class);
});

// ---------------------------------------------------------------------------
// resolveTrackedModels
// ---------------------------------------------------------------------------

it('resolveTrackedModels merges auto_track and tracked, deduplicates, and excludes', function (): void {
    config()->set('moontrail.tracked_models', [TestPost::class]);
    config()->set('moontrail.auto_track_models', [TestPost::class]);
    config()->set('moontrail.menu.exclude_models', []);

    $models = MoonTrailMenuItem::resolveTrackedModels();

    expect($models)->toHaveCount(1)
        ->and($models[0])->toBe(TestPost::class);
});

it('resolveTrackedModels skips non-existent classes', function (): void {
    config()->set('moontrail.tracked_models', ['App\\Models\\NonExistentModel']);
    config()->set('moontrail.auto_track_models', []);
    config()->set('moontrail.menu.exclude_models', []);

    $models = MoonTrailMenuItem::resolveTrackedModels();

    expect($models)->toHaveCount(0);
});
