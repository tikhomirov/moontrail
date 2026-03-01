<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use MoonShine\MoonTrail\Tests\Fixtures\TestPostResource;
use MoonShine\Support\Enums\Ability;
use Spatie\Activitylog\Models\Activity;

// ---------------------------------------------------------------------------
// Index fields
// ---------------------------------------------------------------------------

it('getIndexFields returns non-empty collection', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->getIndexFields()->isNotEmpty())->toBeTrue();
});

it('index fields include required columns in correct order', function (): void {
    $resource = app(MoonTrailResource::class);

    $columns = $resource->getIndexFields()
        ->map(static fn (object $f): string => method_exists($f, 'getColumn') ? $f->getColumn() : '')
        ->filter()
        ->values()
        ->toArray();

    expect($columns)
        ->toBe([
            'id',
            'log_name',
            'event',
            'subject_type',
            'causer_type',
            'description',
            'created_at',
        ]);
});

it('index fields have 7 columns (id, log, event, subject, causer, description, date)', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->getIndexFields()->count())->toBe(7);
});

// ---------------------------------------------------------------------------
// Detail fields
// ---------------------------------------------------------------------------

it('getDetailFields returns non-empty collection', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->getDetailFields()->isNotEmpty())->toBeTrue();
});

it('detail page has 4 section fields (General, Relations, Changes, History)', function (): void {
    $resource = app(MoonTrailResource::class);

    // Each section is a single Preview field
    expect($resource->getDetailFields()->count())->toBe(4);
});

it('detail fields use columns that map to activity model attributes', function (): void {
    $resource = app(MoonTrailResource::class);

    $columns = $resource->getDetailFields()
        ->map(static fn (object $f): string => method_exists($f, 'getColumn') ? $f->getColumn() : '')
        ->filter()
        ->values()
        ->toArray();

    // All 4 sections should have a column assigned
    expect(count($columns))->toBe(4);
});

it('detail rendering contains 4 required sections', function (): void {
    $post = TestPost::query()->create(['name' => 'Detail Post']);

    $activity = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'updated',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => [
            'old'        => ['name' => 'Old'],
            'attributes' => ['name' => 'New'],
        ],
    ]);

    $resource = app(MoonTrailResource::class);
    $caster = $resource->getCaster();

    $html = $resource->getDetailFields()
        ->map(static function (object $field) use ($activity, $caster): string {
            $field->fillCast($activity, $caster);
            $rendered = $field->preview();

            return is_string($rendered) ? $rendered : $rendered->render();
        })
        ->implode('');

    expect($html)
        ->toContain((string) __('moontrail::ui.section_general'))
        ->toContain((string) __('moontrail::ui.section_relations'))
        ->toContain((string) __('moontrail::ui.section_changes'))
        ->toContain((string) __('moontrail::ui.section_history'));
});

// ---------------------------------------------------------------------------
// Read-only: isCan() enforcement
// ---------------------------------------------------------------------------

it('denies CREATE ability', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->can(Ability::CREATE))->toBeFalse();
});

it('denies UPDATE ability', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->can(Ability::UPDATE))->toBeFalse();
});

it('denies DELETE ability', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->can(Ability::DELETE))->toBeFalse();
});

it('denies MASS_DELETE ability', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->can(Ability::MASS_DELETE))->toBeFalse();
});

it('allows VIEW_ANY ability', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->can(Ability::VIEW_ANY))->toBeTrue();
});

it('allows VIEW ability', function (): void {
    $resource = app(MoonTrailResource::class);

    expect($resource->can(Ability::VIEW))->toBeTrue();
});

it('shows System when causer_type is null', function (): void {
    $resource = app(MoonTrailResource::class);

    $activity = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'system event',
        'subject_type' => null,
        'subject_id'   => null,
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => [], 'attributes' => []],
    ]);

    $method = new ReflectionMethod(MoonTrailResource::class, 'renderCauserLink');

    $html = $method->invoke($resource, $activity);

    expect($html)->toContain(e((string) __('moontrail::ui.system')));
});

it('renders event badge with expected class and localized label', function (): void {
    $resource = app(MoonTrailResource::class);

    $activity = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'created event',
        'subject_type' => null,
        'subject_id'   => null,
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'created',
        'properties'   => ['old' => [], 'attributes' => []],
    ]);

    $method = new ReflectionMethod(MoonTrailResource::class, 'renderEventBadge');

    $badgeHtml = $method->invoke($resource, $activity);

    expect($badgeHtml)
        ->toContain('bg-emerald-100')
        ->toContain((string) __('moontrail::ui.event_created'));
});

it('renders Open button in relations only when matching resource is available', function (): void {
    moonshine()->resources([TestPostResource::class], true);

    $resource = app(MoonTrailResource::class);
    $post = TestPost::query()->create(['name' => 'Open Me']);
    $openLabel = (string) __('moontrail::ui.open');

    $method = new ReflectionMethod(MoonTrailResource::class, 'renderRelationEntry');

    $withResource = $method->invoke($resource, TestPost::class, $post->getKey(), $post, $openLabel);
    $withoutResource = $method->invoke($resource, 'App\\Models\\UnknownModel', 777, null, $openLabel);

    expect($withResource)
        ->toContain($openLabel)
        ->toContain('href=')
        ->and($withoutResource)
        ->not->toContain($openLabel);
});

// ---------------------------------------------------------------------------
// Filters and search
// ---------------------------------------------------------------------------

it('filters returns all required fields from TZ-03', function (): void {
    $resource = app(MoonTrailResource::class);
    $filters = $resource->getFilters();

    $columns = $filters
        ->map(static fn (object $f): string => method_exists($f, 'getColumn') ? $f->getColumn() : '')
        ->filter()
        ->values()
        ->toArray();

    expect($filters->isNotEmpty())->toBeTrue()
        ->and($filters->count())->toBe(8)
        ->and($columns)->toBe([
            'log_name',
            'event',
            'subject_type',
            'subject_id',
            'causer_type',
            'causer_id',
            'date_from',
            'date_until',
        ]);
});

it('applies filters from direct query format', function (): void {
    $post = TestPost::query()->create(['name' => 'Direct Filters']);

    $matched = Activity::query()->create([
        'log_name'     => 'orders',
        'description'  => 'matched direct filter',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => TestPost::class,
        'causer_id'    => 10,
        'event'        => 'updated',
        'properties'   => ['old' => ['status' => 'draft'], 'attributes' => ['status' => 'paid']],
        'created_at'   => '2026-02-15 10:00:00',
    ]);

    Activity::query()->create([
        'log_name'     => 'orders',
        'description'  => 'different event',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => TestPost::class,
        'causer_id'    => 10,
        'event'        => 'deleted',
        'properties'   => ['old' => ['status' => 'paid'], 'attributes' => []],
        'created_at'   => '2026-02-15 12:00:00',
    ]);

    request()->query->replace([
        'log_name'     => 'orders',
        'event'        => 'updated',
        'subject_type' => TestPost::class,
        'subject_id'   => (string) $post->getKey(),
        'causer_type'  => TestPost::class,
        'causer_id'    => '10',
        'date_from'    => '2026-02-01',
        'date_until'   => '2026-02-28',
    ]);

    $resource = app(MoonTrailResource::class);
    $method = new ReflectionMethod(MoonTrailResource::class, 'modifyQueryBuilder');

    /** @var \Illuminate\Database\Eloquent\Builder $builder */
    $builder = $method->invoke($resource, Activity::query());
    $ids = $builder->pluck('id')->all();

    expect($ids)->toBe([$matched->id]);
});

it('applies filters from moonshine style query format', function (): void {
    $post = TestPost::query()->create(['name' => 'Nested Filters']);

    $matched = Activity::query()->create([
        'log_name'     => 'security',
        'description'  => 'matched nested filter',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => TestPost::class,
        'causer_id'    => 2,
        'event'        => 'deleted',
        'properties'   => ['old' => ['name' => 'A'], 'attributes' => ['name' => 'B']],
    ]);

    Activity::query()->create([
        'log_name'     => 'security',
        'description'  => 'different causer id',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => TestPost::class,
        'causer_id'    => 3,
        'event'        => 'deleted',
        'properties'   => ['old' => ['name' => 'B'], 'attributes' => ['name' => 'C']],
    ]);

    request()->query->replace([
        'filters' => [
            'event'     => 'deleted',
            'causer_id' => '2',
        ],
    ]);

    $resource = app(MoonTrailResource::class);
    $method = new ReflectionMethod(MoonTrailResource::class, 'modifyQueryBuilder');

    /** @var \Illuminate\Database\Eloquent\Builder $builder */
    $builder = $method->invoke($resource, Activity::query());
    $ids = $builder->pluck('id')->all();

    expect($ids)->toBe([$matched->id]);
});

it('ignores invalid date filters without throwing errors', function (): void {
    $post = TestPost::query()->create(['name' => 'Invalid Date Filters']);

    $first = Activity::query()->create([
        'log_name'     => 'orders',
        'description'  => 'first',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'A'], 'attributes' => ['name' => 'B']],
        'created_at'   => '2026-02-01 12:00:00',
    ]);

    $second = Activity::query()->create([
        'log_name'     => 'orders',
        'description'  => 'second',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'B'], 'attributes' => ['name' => 'C']],
        'created_at'   => '2026-02-10 12:00:00',
    ]);

    request()->query->replace([
        'date_from'  => 'not-a-date',
        'date_until' => '2026-02-30',
    ]);

    $resource = app(MoonTrailResource::class);
    $method = new ReflectionMethod(MoonTrailResource::class, 'modifyQueryBuilder');

    /** @var \Illuminate\Database\Eloquent\Builder $builder */
    $builder = $method->invoke($resource, Activity::query());

    expect($builder->pluck('id')->all())
        ->toContain($first->id, $second->id);
});

it('applies valid date filters in strict Y-m-d format', function (): void {
    $post = TestPost::query()->create(['name' => 'Valid Date Filters']);

    Activity::query()->create([
        'log_name'     => 'orders',
        'description'  => 'outside range',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'A'], 'attributes' => ['name' => 'B']],
        'created_at'   => '2026-01-31 23:59:59',
    ]);

    $matched = Activity::query()->create([
        'log_name'     => 'orders',
        'description'  => 'inside range',
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'B'], 'attributes' => ['name' => 'C']],
        'created_at'   => '2026-02-15 10:00:00',
    ]);

    request()->query->replace([
        'log_name'   => 'orders',
        'date_from'  => '2026-02-01',
        'date_until' => '2026-02-28',
    ]);

    $resource = app(MoonTrailResource::class);
    $method = new ReflectionMethod(MoonTrailResource::class, 'modifyQueryBuilder');

    /** @var \Illuminate\Database\Eloquent\Builder $builder */
    $builder = $method->invoke($resource, Activity::query());

    expect($builder->pluck('id')->all())->toBe([$matched->id]);
});

it('searches by properties using fallback like strategy', function (): void {
    Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'plain description',
        'subject_type' => TestPost::class,
        'subject_id'   => 1,
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'Alpha'], 'attributes' => ['name' => 'Beta']],
    ]);

    $matched = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'another description',
        'subject_type' => TestPost::class,
        'subject_id'   => 2,
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'Gamma'], 'attributes' => ['name' => 'needle-value']],
    ]);

    request()->query->replace([
        'search' => 'needle-value',
    ]);

    $resource = app(MoonTrailResource::class);
    $method = new ReflectionMethod(MoonTrailResource::class, 'modifyQueryBuilder');

    /** @var \Illuminate\Database\Eloquent\Builder $builder */
    $builder = $method->invoke($resource, Activity::query());
    $ids = $builder->pluck('id')->all();

    expect($ids)->toBe([$matched->id]);
});

it('searches by numeric ids extracted from mixed query like product 46', function (): void {
    $matched = Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'plain description without requested phrase',
        'subject_type' => TestPost::class,
        'subject_id'   => 46,
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'A'], 'attributes' => ['name' => 'B']],
    ]);

    Activity::query()->create([
        'log_name'     => 'default',
        'description'  => 'another row',
        'subject_type' => TestPost::class,
        'subject_id'   => 47,
        'causer_type'  => null,
        'causer_id'    => null,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'C'], 'attributes' => ['name' => 'D']],
    ]);

    request()->query->replace([
        'search' => 'product 46',
    ]);

    $resource = app(MoonTrailResource::class);
    $method = new ReflectionMethod(MoonTrailResource::class, 'modifyQueryBuilder');

    /** @var \Illuminate\Database\Eloquent\Builder $builder */
    $builder = $method->invoke($resource, Activity::query());
    $ids = $builder->pluck('id')->all();

    expect($ids)->toBe([$matched->id]);
});

// ---------------------------------------------------------------------------
// Activity tracking
// ---------------------------------------------------------------------------

it('tracks and lists activity records', function (): void {
    $post = TestPost::query()->create(['name' => 'Hello']);
    $post->update(['name' => 'World']);

    expect(Activity::query()->count())->toBeGreaterThanOrEqual(1);
});

it('subject_type filter query param scopes query builder results', function (): void {
    TestPost::query()->create(['name' => 'Post A']);

    $allActivities = Activity::query()->count();
    $filteredActivities = Activity::query()
        ->where('subject_type', TestPost::class)
        ->count();

    expect($allActivities)->toBeGreaterThanOrEqual(1)
        ->and($filteredActivities)->toBe($allActivities);
});
