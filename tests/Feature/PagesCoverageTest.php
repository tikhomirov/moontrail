<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Pages\MoonTrailDetailPage;
use MoonShine\MoonTrail\Pages\MoonTrailPage;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

beforeEach(function (): void {
    View::replaceNamespace('moontrail', __DIR__ . '/../../resources/views');
});

it('renders MoonTrailPage with stats and title', function (): void {
    $post = TestPost::query()->create(['name' => 'Page Test']);
    app(ActivityLoggerContract::class)->log($post, 'created', ['description' => 'Test log']);

    /** @var MoonTrailPage $page */
    $page = app(MoonTrailPage::class);

    expect($page->getTitle())->not->toBeEmpty();

    $html = (string) $page->render();
    expect($html)->not->toBeEmpty();
});

it('renders MoonTrailDetailPage main layer for activity record', function (): void {
    $post = TestPost::query()->create(['name' => 'Detail Test Post']);
    app(ActivityLoggerContract::class)->log($post, 'created', ['description' => 'Detail test log']);

    $resource = app(MoonTrailResource::class);
    $item = $resource->getModel()::query()->first();

    if ($item === null) {
        $this->markTestSkipped('No activity item found');
    }

    $resource->setItem($item);

    $page = app(MoonTrailDetailPage::class);
    $page->setResource($resource);

    $ref = new ReflectionMethod($page, 'mainLayer');
    $layer = $ref->invoke($page);

    expect($layer)->not->toBeEmpty();
});
