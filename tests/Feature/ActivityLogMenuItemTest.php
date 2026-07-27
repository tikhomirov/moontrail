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
    config()->set('moontrail.menu.models', []);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);

    $item = MoonTrailMenuItem::make('Custom Label');

    expect($item)->toBeInstanceOf(MenuItem::class);
});

it('uses menu.label config when no argument given', function (): void {
    config()->set('moontrail.menu.models', []);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.label', 'Config Label');
    config()->set('moontrail.menu.show_all', true);

    $item = MoonTrailMenuItem::make();

    expect($item)->toBeInstanceOf(MenuItem::class);
});

// ---------------------------------------------------------------------------
// show_children = false
// ---------------------------------------------------------------------------

it('returns a single MenuItem when show_children is false (ignores models)', function (): void {
    config()->set('moontrail.menu.models', [TestPost::class]);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', false);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuItem::class);
});

// ---------------------------------------------------------------------------
// no tracked models
// ---------------------------------------------------------------------------

it('returns a single MenuItem when no tracked models and show_all_item is true', function (): void {
    config()->set('moontrail.menu.models', []);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', true);
    config()->set('moontrail.menu.show_all', true);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuItem::class);
});

it('returns null when no tracked models and show_all_item is false', function (): void {
    config()->set('moontrail.menu.models', []);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', true);
    config()->set('moontrail.menu.show_all', false);

    expect(MoonTrailMenuItem::make())->toBeNull();
});

// ---------------------------------------------------------------------------
// tracked models present
// ---------------------------------------------------------------------------

it('returns a MenuGroup when tracked models are configured', function (): void {
    config()->set('moontrail.menu.models', [TestPost::class]);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', true);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuGroup::class);
});

it('creates sub-items for each tracked model plus All item', function (): void {
    config()->set('moontrail.menu.models', [TestPost::class]);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', true);
    config()->set('moontrail.menu.show_all', true);
    config()->set('moontrail.menu.exclude', []);

    /** @var MenuGroup $group */
    $group = MoonTrailMenuItem::make();

    expect($group)->toBeInstanceOf(MenuGroup::class)
        ->and($group->getItems())->toHaveCount(2);
});

it('hides All item when show_all_item is false', function (): void {
    config()->set('moontrail.menu.models', [TestPost::class]);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', true);
    config()->set('moontrail.menu.show_all', false);
    config()->set('moontrail.menu.exclude', []);

    /** @var MenuGroup $group */
    $group = MoonTrailMenuItem::make();

    expect($group->getItems())->toHaveCount(1);
});

it('excludes models listed in menu.exclude', function (): void {
    config()->set('moontrail.menu.models', [TestPost::class]);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.enabled', true);
    config()->set('moontrail.menu.group_models', true);
    config()->set('moontrail.menu.show_all', true);
    config()->set('moontrail.menu.exclude', [TestPost::class]);

    $result = MoonTrailMenuItem::make();

    expect($result)->toBeInstanceOf(MenuItem::class);
});

// ---------------------------------------------------------------------------
// resolveTrackedModels
// ---------------------------------------------------------------------------

it('resolveTrackedModels merges auto_track and tracked, deduplicates, and excludes', function (): void {
    config()->set('moontrail.menu.models', [TestPost::class]);
    config()->set('moontrail.tracking.auto.models', [TestPost::class]);
    config()->set('moontrail.menu.exclude', []);

    $models = MoonTrailMenuItem::resolveTrackedModels();

    expect($models)->toHaveCount(1)
        ->and($models[0])->toBe(TestPost::class);
});

it('resolveTrackedModels skips non-existent classes', function (): void {
    config()->set('moontrail.menu.models', ['App\\Models\\NonExistentModel']);
    config()->set('moontrail.tracking.auto.models', []);
    config()->set('moontrail.menu.exclude', []);

    $models = MoonTrailMenuItem::resolveTrackedModels();

    expect($models)->toHaveCount(0);
});
