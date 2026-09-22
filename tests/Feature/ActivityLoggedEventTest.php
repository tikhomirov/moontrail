<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Events\ActivityLogged;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('dispatches ActivityLogged event when activity is logged', function (): void {
    Event::fake([ActivityLogged::class]);

    $post = TestPost::query()->create([
        'name' => 'Event Post',
        'body' => 'Event Body',
    ]);

    /** @var ActivityLoggerContract $logger */
    $logger = app(ActivityLoggerContract::class);
    $logger->log($post, 'custom_event', ['description' => 'Test event log']);

    Event::assertDispatched(ActivityLogged::class, function (ActivityLogged $event) use ($post): bool {
        return $event->model->getKey() === $post->getKey()
            && $event->event === 'custom_event';
    });
});
