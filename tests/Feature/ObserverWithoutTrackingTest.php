<?php

declare(strict_types=1);

use MoonShine\MoonTrail\MoonTrailObserver;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('skips version and activity tracking inside withoutTracking callback and restores state', function (): void {
    expect(MoonTrailObserver::isSuspended())->toBeFalse();

    $result = MoonTrailObserver::withoutTracking(function (): string {
        expect(MoonTrailObserver::isSuspended())->toBeTrue();

        $post = TestPost::query()->create([
            'name' => 'Unversioned Post',
            'body' => 'Unversioned Body',
        ]);

        expect($post->versions()->count())->toBe(0);

        return 'done';
    });

    expect($result)->toBe('done')
        ->and(MoonTrailObserver::isSuspended())->toBeFalse();
});
