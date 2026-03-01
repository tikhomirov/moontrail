<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use Spatie\Activitylog\Models\Activity;

it('tracks model changes in activity_log and model_versions', function (): void {
    $post = TestPost::query()->create([
        'name' => 'Initial',
        'body' => 'Body',
    ]);

    $post->update([
        'name' => 'Updated',
    ]);

    expect(Activity::query()->where('subject_type', $post->getMorphClass())->where('subject_id', $post->getKey())->count())
        ->toBeGreaterThanOrEqual(2)
        ->and(ModelVersion::query()->forModel($post)->count())
        ->toBeGreaterThanOrEqual(2);
});
