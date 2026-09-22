<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Activity\DatabaseActivityAdapter;
use MoonShine\MoonTrail\Activity\DatabaseActivityQuery;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('exercises DatabaseActivityQuery pagination, find, stats, and distinct values', function (): void {
    $post = TestPost::query()->create(['name' => 'Query Post']);
    $admin = TestAdmin::query()->create(['name' => 'Admin Query', 'email' => 'admin-query@example.test']);

    $activity = MoonTrailActivity::query()->create([
        'log_name'     => 'test_log',
        'subject_type' => $post->getMorphClass(),
        'subject_id'   => $post->getKey(),
        'model_type'   => $post->getMorphClass(),
        'model_id'     => $post->getKey(),
        'event'        => 'created',
        'properties'   => ['attributes' => ['name' => 'Query Post']],
        'causer_type'  => $admin->getMorphClass(),
        'causer_id'    => $admin->getKey(),
        'description'  => 'Created post',
    ]);

    $query = new DatabaseActivityQuery;

    expect($query->modelClass())->toBe(MoonTrailActivity::class);

    $found = $query->find($activity->id);
    expect($found)->not->toBeNull()
        ->and($found->getId())->toBe($activity->id)
        ->and($found->getEvent())->toBe('created')
        ->and($found->getDescription())->toBe('Created post')
        ->and($found->getSubjectType())->toBe($post->getMorphClass())
        ->and($found->getSubjectId())->toBe($post->getKey())
        ->and($found->getCauserType())->toBe($admin->getMorphClass())
        ->and($found->getCauserId())->toBe($admin->getKey())
        ->and($found->getProperties())->toHaveKey('attributes');

    expect($query->find(999999))->toBeNull();

    $stats = $query->stats();
    expect($stats)->toHaveKeys(['total', 'created', 'updated', 'deleted', 'other'])
        ->and($stats['total'])->toBeGreaterThanOrEqual(1)
        ->and($stats['created'])->toBeGreaterThanOrEqual(1);

    $paginated = $query->paginate(['event' => 'created', 'search' => 'Created post']);
    expect($paginated->total())->toBeGreaterThanOrEqual(1);

    $events = $query->distinctValues('event');
    expect($events)->toContain('created');

    $subjects = $query->distinctValues('subject_type');
    expect($subjects)->toContain($post->getMorphClass());

    $invalid = $query->distinctValues('non_existent_column');
    expect($invalid)->toBe([]);
});

it('exercises DatabaseActivityAdapter methods directly', function (): void {
    $activity = new MoonTrailActivity([
        'log_name'     => 'default',
        'subject_type' => TestPost::class,
        'subject_id'   => 42,
        'event'        => 'updated',
        'properties'   => ['old' => ['name' => 'A'], 'attributes' => ['name' => 'B']],
        'causer_type'  => TestAdmin::class,
        'causer_id'    => 1,
        'description'  => 'Updated test post',
        'created_at'   => now(),
    ]);
    $activity->id = 123;

    $adapter = new DatabaseActivityAdapter($activity);

    expect($adapter->model())->toBe($activity)
        ->and($adapter->getId())->toBe(123)
        ->and($adapter->getEvent())->toBe('updated')
        ->and($adapter->getProperties())->toHaveKey('old')
        ->and($adapter->getCreatedAt())->not->toBeNull()
        ->and($adapter->getSubjectType())->toBe(TestPost::class)
        ->and($adapter->getSubjectId())->toBe(42)
        ->and($adapter->getCauserType())->toBe(TestAdmin::class)
        ->and($adapter->getCauserId())->toBe(1)
        ->and($adapter->getDescription())->toBe('Updated test post');
});

it('exercises MoonTrailActivity model accessors and mutators', function (): void {
    $activity = new MoonTrailActivity;
    $activity->subject_type = TestPost::class;
    $activity->subject_id = 100;
    $activity->event = 'deleted';
    $activity->properties = ['old' => ['name' => 'Deleted']];

    expect($activity->getSubjectTypeAttribute(null))->toBe(TestPost::class)
        ->and($activity->getSubjectIdAttribute(null))->toBe(100)
        ->and($activity->getId())->toBe(0)
        ->and($activity->getEvent())->toBe('deleted')
        ->and($activity->getProperties())->toHaveKey('old');
});
