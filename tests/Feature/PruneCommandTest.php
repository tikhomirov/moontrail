<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Versioning\VersionManager;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    config()->set('moontrail.versioning.enabled', false);
});

it('prunes model versions older than given days', function (): void {
    $post = TestPost::query()->create(['name' => 'Old Post']);
    $manager = app(VersionManager::class);

    $version = $manager->createVersion($post, 'created');

    ModelVersion::query()
        ->where('id', $version->id)
        ->update(['created_at' => now()->subDays(100)]);

    expect(ModelVersion::query()->count())->toBe(1);

    $this->artisan('moontrail:prune', ['--days' => 90, '--versions-only' => true]);

    expect(ModelVersion::query()->count())->toBe(0);
});

it('keeps model versions newer than given days', function (): void {
    $post = TestPost::query()->create(['name' => 'New Post']);
    $manager = app(VersionManager::class);

    $manager->createVersion($post, 'created');

    $this->artisan('moontrail:prune', ['--days' => 90, '--versions-only' => true]);

    expect(ModelVersion::query()->count())->toBe(1);
});

it('prunes activity log records older than given days', function (): void {
    $post = TestPost::query()->create(['name' => 'Post']);

    $activity = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'created',
        'subject_type' => $post->getMorphClass(),
        'subject_id'   => $post->getKey(),
        'event'        => 'created',
        'properties'   => '{}',
        'created_at'   => now()->subDays(200),
        'updated_at'   => now()->subDays(200),
    ]);

    $countBefore = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('created_at', '<', now()->subDays(90))
        ->count();

    expect($countBefore)->toBe(1);

    $this->artisan('moontrail:prune', ['--days' => 90, '--activity-only' => true]);

    $countAfter = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('created_at', '<', now()->subDays(90))
        ->count();

    expect($countAfter)->toBe(0);
});

it('does not prune versions for a different model class when --model is given', function (): void {
    $post = TestPost::query()->create(['name' => 'Test']);
    $manager = app(VersionManager::class);

    $version = $manager->createVersion($post, 'created');

    ModelVersion::query()
        ->where('id', $version->id)
        ->update(['created_at' => now()->subDays(200)]);

    $this->artisan('moontrail:prune', [
        '--days'          => 90,
        '--model'         => 'SomeOtherModel',
        '--versions-only' => true,
    ]);

    expect(ModelVersion::query()->count())->toBe(1);
});

it('fails fast when mutually exclusive prune flags are provided', function (): void {
    expect(fn () => $this->artisan('moontrail:prune', [
        '--versions-only' => true,
        '--activity-only' => true,
    ]))->toThrow(\RuntimeException::class, 'mutually exclusive');
});

it('fails fast when --days is zero or negative', function (): void {
    expect(fn () => $this->artisan('moontrail:prune', [
        '--days'          => 0,
        '--versions-only' => true,
    ]))->toThrow(\RuntimeException::class, '--days must be a positive integer');

    expect(fn () => $this->artisan('moontrail:prune', [
        '--days'          => -5,
        '--versions-only' => true,
    ]))->toThrow(\RuntimeException::class, '--days must be a positive integer');
});

it('writes structured prune summary log', function (): void {
    Log::spy();

    $this->artisan('moontrail:prune', ['--days' => 30, '--versions-only' => true])
        ->assertSuccessful();

    Log::shouldHaveReceived('info')->withArgs(
        static fn (string $message, array $context): bool => $message === 'MoonTrail: prune_summary'
            && isset($context['days'], $context['versions_only'], $context['activity_only']),
    );
});
