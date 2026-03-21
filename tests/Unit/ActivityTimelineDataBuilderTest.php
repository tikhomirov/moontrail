<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Support\ActivityTimelineDataBuilder;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('computes changes count for versions with activity', function (): void {
    $post = TestPost::query()->create(['name' => 'Initial']);
    $post->update(['name' => 'Updated']);

    $versions = ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->with(['activity'])
        ->latest('version')
        ->get()
        ->all();

    expect($versions)->not->toBeEmpty();

    $builder = new ActivityTimelineDataBuilder;
    $counts = $builder->computeChangesCount($versions);

    // Every version should have a count entry
    foreach ($versions as $version) {
        expect($counts)->toHaveKey($version->id);
        expect($counts[$version->id])->toBeInt()->toBeGreaterThanOrEqual(0);
    }

    // The 'updated' version should report at least 1 changed field
    $updatedVersion = collect($versions)->first(fn (ModelVersion $v): bool => $v->event === 'updated');

    if ($updatedVersion !== null) {
        expect($counts[$updatedVersion->id])->toBeGreaterThan(0);
    }
});

it('returns 0 for versions without linked activity', function (): void {
    $post = TestPost::query()->create(['name' => 'Solo']);

    $versions = ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->get()
        ->all();

    // Detach activity_id to simulate no-activity version
    foreach ($versions as $version) {
        $version->activity_id = null;
    }

    $builder = new ActivityTimelineDataBuilder;
    $counts = $builder->computeChangesCount($versions);

    foreach ($versions as $version) {
        expect($counts[$version->id])->toBe(0);
    }
});
