<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use MoonShine\MoonTrail\Activity\DatabaseActivityAdapter;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Diff\DefaultActivityFormatter;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\MoonTrailObserver;
use MoonShine\MoonTrail\Support\ActivityAuthorizationResolver;
use MoonShine\MoonTrail\Support\ActivityRecordFactory;
use MoonShine\MoonTrail\Support\MoonTrailMenuItem;
use MoonShine\MoonTrail\Tests\Fixtures\DenyRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('tests ActivityRecordFactory conversions and exceptions', function (): void {
    $factory = new ActivityRecordFactory;

    $activity = new MoonTrailActivity(['event' => 'created']);
    $record = $factory->fromModel($activity);
    expect($record)->toBeInstanceOf(ActivityRecordContract::class);

    $backed = new DatabaseActivityAdapter($activity);
    expect($factory->fromModel($backed->model()))->toBeInstanceOf(ActivityRecordContract::class);

    $dummyModel = new class extends Model {};
    expect(fn () => $factory->fromModel($dummyModel))->toThrow(RuntimeException::class);
});

it('tests DefaultActivityFormatter with standard and custom events', function (): void {
    $formatter = new DefaultActivityFormatter;

    $activity1 = new MoonTrailActivity([
        'event'       => 'created',
        'description' => 'Created description',
    ]);
    $adapter1 = new DatabaseActivityAdapter($activity1);
    $result1 = $formatter->format($adapter1);

    expect($result1['description'])->toBe(ActivityEvent::Created->label())
        ->and($result1['icon'])->toBe(ActivityEvent::Created->icon())
        ->and($result1['color'])->toBe(ActivityEvent::Created->color());

    $activity2 = new MoonTrailActivity([
        'event'       => 'custom_event',
        'description' => 'Custom description',
    ]);
    $adapter2 = new DatabaseActivityAdapter($activity2);
    $result2 = $formatter->format($adapter2);

    expect($result2['description'])->toBe('Custom description')
        ->and($result2['icon'])->toBe('info')
        ->and($result2['color'])->toBe('gray');
});

it('tests ActivityAuthorizationResolver gate checks and authorization exceptions', function (): void {
    $resolver = new ActivityAuthorizationResolver;

    // Subject type is null or invalid
    $act1 = new DatabaseActivityAdapter(new MoonTrailActivity(['subject_type' => null]));
    expect($resolver->canView($act1))->toBeTrue();
    $resolver->authorize($act1);

    // Subject type with deny policy
    $post = TestPost::query()->create(['name' => 'Deny Auth Post']);
    Gate::policy(TestPost::class, DenyRollbackPolicy::class);

    $act2 = new DatabaseActivityAdapter(new MoonTrailActivity([
        'subject_type' => TestPost::class,
        'subject_id'   => $post->getKey(),
    ]));

    expect($resolver->canView($act2))->toBeFalse();
    expect(fn () => $resolver->authorize($act2))->toThrow(AuthorizationException::class);
});

it('tests MoonTrailObserver lifecycle events and error handling', function (): void {
    $post = TestPost::query()->create(['name' => 'Observer Lifecycle Post']);

    /** @var MoonTrailObserver $observer */
    $observer = app(MoonTrailObserver::class);

    // Test suspend/resume static methods
    MoonTrailObserver::suspend();
    expect(MoonTrailObserver::isSuspended())->toBeTrue();
    $observer->created($post);
    $observer->updated($post);
    $observer->deleted($post);
    $observer->restored($post);

    MoonTrailObserver::resume();
    expect(MoonTrailObserver::isSuspended())->toBeFalse();

    // Test events execution
    $observer->created($post);
    $post->name = 'Observer Changed';
    $observer->updated($post);
    $observer->deleted($post);
    $observer->restored($post);

    // Test error reporting config
    config()->set('moontrail.auto_track.on_error', 'report');
    $observer->created($post);
});

it('tests MoonTrailMenuItem configuration options', function (): void {
    config()->set('moontrail.ui.badge', true);
    config()->set('moontrail.ui.menu_default_open', true);

    $item = MoonTrailMenuItem::make();
    expect($item)->not->toBeNull();

    $item2 = MoonTrailMenuItem::make(label: 'Custom Logs');
    expect($item2)->not->toBeNull()
        ->and($item2->getLabel())->toBe('Custom Logs');

    config()->set('moontrail.menu.enabled', false);
    expect(MoonTrailMenuItem::make())->toBeNull();
});
