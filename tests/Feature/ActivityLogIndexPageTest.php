<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Components\ActivityTimeline;
use MoonShine\MoonTrail\Pages\MoonTrailIndexPage;
use MoonShine\MoonTrail\Tests\Fixtures\AllowRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Tests\Fixtures\TestPostResource;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    View::replaceNamespace('moontrail', __DIR__ . '/../../resources/views');
});

/**
 * Helper: call a protected/private method on $object and return result.
 *
 * @param mixed[] $args
 */
function callProtected(object $object, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($object, $method);

    return $ref->invoke($object, ...$args);
}

// ─── KPI block ─────────────────────────────────────────────────────────────

it('renderKpiBlock contains ms-al-kpi-grid', function (): void {
    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderKpiBlock');

    expect($html)->toContain('ms-al-kpi-grid');
});

it('renderKpiBlock contains five kpi card modifiers', function (): void {
    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderKpiBlock');

    expect($html)
        ->toContain('ms-al-kpi-card--total')
        ->toContain('ms-al-kpi-card--created')
        ->toContain('ms-al-kpi-card--updated')
        ->toContain('ms-al-kpi-card--deleted')
        ->toContain('ms-al-kpi-card--other');
});

it('renderKpiBlock counts activity records correctly', function (): void {
    // Insert 3 'created' events
    foreach (range(1, 3) as $_) {
        $a = activity()->causedByAnonymous()->log('c');
        $a->update(['event' => 'created']);
    }

    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderKpiBlock');

    expect($html)->toContain('ms-al-kpi-value');

    $total = Activity::query()->count();
    expect($html)->toContain((string) $total);
});

// ─── Filter chips ──────────────────────────────────────────────────────────

it('renderActiveFilterChips returns empty string when no filters active', function (): void {
    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderActiveFilterChips');

    expect($html)->toBe('');
});

it('renderActiveFilterChips returns chip html when event param present', function (): void {
    $request = request()->replace(['event' => 'created']);
    app()->instance('request', $request);

    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderActiveFilterChips');

    expect($html)->toContain('ms-al-filter-chip');
});

it('renderActiveFilterChips shows translated event label', function (): void {
    $request = request()->replace(['event' => 'created']);
    app()->instance('request', $request);

    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderActiveFilterChips');

    // Must contain the translated label (not raw 'created')
    expect($html)->toContain((string) __('moontrail::ui.event_created'));
});

it('renderActiveFilterChips returns clear-all link when multiple filters active', function (): void {
    $request = request()->replace(['event' => 'deleted', 'log_name' => 'default']);
    app()->instance('request', $request);

    $page = app(MoonTrailIndexPage::class);

    /** @var string $html */
    $html = callProtected($page, 'renderActiveFilterChips');

    expect($html)
        ->toContain('ms-al-filter-chip')
        ->toContain('ms-al-filter-clear-all')
        ->toContain((string) __('moontrail::ui.filter_clear_all'));
});

// ─── Accessibility attributes in timeline ─────────────────────────────────

it('timeline renders role=list on version container', function (): void {
    $post = TestPost::query()->create(['name' => 'A']);
    $post->update(['name' => 'B']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->toContain('role="list"');
});

it('timeline renders role=listitem on each version entry', function (): void {
    $post = TestPost::query()->create(['name' => 'A']);
    $post->update(['name' => 'B']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->toContain('role="listitem"');
});

it('timeline renders aria-expanded on diff toggle button', function (): void {
    $post = TestPost::query()->create(['name' => 'A']);
    $post->update(['name' => 'B']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->toContain('aria-expanded');
});

it('timeline renders datetime attribute on time element', function (): void {
    $post = TestPost::query()->create(['name' => 'A']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->toContain('datetime=');
});

it('timeline renders aria-live on diff panel region', function (): void {
    $post = TestPost::query()->create(['name' => 'A']);
    $post->update(['name' => 'B']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)->toContain('aria-live');
});

it('timeline wraps rollback modal in role dialog', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $admin = TestAdmin::query()->create([
        'name'  => 'Dialog Admin',
        'email' => 'dialog-admin@example.test',
    ]);

    $guard = MoonShineAuth::getGuard();
    $guard->setUser($admin);

    $post = TestPost::query()->create(['name' => 'A']);
    $post->update(['name' => 'B']);

    $resource = app(TestPostResource::class);
    $resource->setItem($post);

    $component = ActivityTimeline::make('History', $resource);
    $html = (string) $component->render();

    expect($html)
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('aria-labelledby="ms-al-rollback-title"');
});

// ─── Accessibility attributes in diff viewer ──────────────────────────────

it('diff viewer table contains scope=col on th elements', function (): void {
    $post = TestPost::query()->create(['name' => 'A']);
    $post->update(['name' => 'B']);

    $versions = \MoonShine\MoonTrail\Models\ModelVersion::query()
        ->where('versionable_type', $post->getMorphClass())
        ->where('versionable_id', $post->getKey())
        ->with('author')
        ->latest('version')
        ->get();

    $updatedVersion = $versions->firstWhere('event', 'updated');

    if ($updatedVersion === null || $updatedVersion->activity_id === null) {
        $this->markTestSkipped('Need activity_id for diff viewer test');
    }

    $activity = \Spatie\Activitylog\Models\Activity::query()->find($updatedVersion->activity_id);

    if ($activity === null) {
        $this->markTestSkipped('Activity record not found');
    }

    $changes = \MoonShine\MoonTrail\Diff\DiffComputer::fromActivity($activity);
    $html = (string) view('moontrail::components.diff-viewer', [
        'changes' => $changes,
        'compact' => false,
    ])->render();

    expect($html)->toContain('scope="col"');
});
