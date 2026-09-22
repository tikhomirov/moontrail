<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Components\ActivityTab;
use MoonShine\MoonTrail\Components\DiffViewer;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Enums\ChangeType;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Tests\Fixtures\TestPostResource;
use MoonShine\MoonTrail\Tests\Fixtures\TestTabPostResource;

it('renders ActivityTab component with default and customized settings', function (): void {
    $post = TestPost::query()->create(['name' => 'Tab Post']);
    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $tab = new ActivityTab(resource: $resource, limit: 10, showRollback: false);
    $html = (string) $tab->render();

    expect($html)->not->toBeEmpty();

    $defaultTab = new ActivityTab(resource: $resource);
    $defaultHtml = (string) $defaultTab->render();

    expect($defaultHtml)->not->toBeEmpty();
});

it('exercises DiffViewer compact and onlyChanged modes', function (): void {
    $changes = [
        'title' => new FieldChange('title', 'Old', 'New', ChangeType::Modified),
        'body'  => new FieldChange('body', 'Same', 'Same', ChangeType::Unchanged),
    ];

    $viewer = DiffViewer::make($changes)->compact()->onlyChanged();
    $html = (string) $viewer->render();

    expect($html)->toContain('title');
});

it('exercises HasMoonTrailActivity trait options', function (): void {
    $model = new \MoonShine\MoonTrail\Tests\Fixtures\TestPostWithActivityTrait;
    $model->name = 'Trait Post';
    $model->save();

    $options = $model->getActivitylogOptions();

    expect($options)->not->toBeNull()
        ->and($options->logOnlyDirty)->toBeTrue();
});

it('exercises HasMoonTrailVersioning methods', function (): void {
    $post = TestPost::query()->create(['name' => 'Version Test']);
    $post->update(['name' => 'Version Test 2']);

    expect($post->currentVersionNumber())->toBe(2)
        ->and($post->latestVersion()->first())->not->toBeNull()
        ->and($post->isRollbackAllowed())->toBeFalse()
        ->and($post->getVersionExcludedFields())->toEqual(['password', 'remember_token']);
});

it('exercises WithMoonTrailTab resource integration', function (): void {
    /** @var TestTabPostResource $instance */
    $instance = app(TestTabPostResource::class);
    $instance->testBoot();

    expect($instance->publicLabel())->not->toBeEmpty()
        ->and($instance->publicLimit())->toBeGreaterThan(0)
        ->and($instance->publicShowRollback())->toBeTrue();
});
