<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Support\ActivityLogFilterData;

it('reads direct query params from request', function (): void {
    $request = request()->replace([
        'log_name'     => 'default',
        'event'        => 'created',
        'subject_type' => 'App\\Post',
        'subject_id'   => '42',
        'causer_type'  => 'App\\User',
        'causer_id'    => '7',
        'date_from'    => '2024-01-01',
        'date_until'   => '2024-12-31',
        'search'       => 'hello',
    ]);
    app()->instance('request', $request);

    $data = ActivityLogFilterData::fromRequest();

    expect($data->logName)->toBe('default')
        ->and($data->event)->toBe('created')
        ->and($data->subjectType)->toBe('App\\Post')
        ->and($data->subjectId)->toBe('42')
        ->and($data->causerType)->toBe('App\\User')
        ->and($data->causerId)->toBe('7')
        ->and($data->dateFrom)->toBe('2024-01-01')
        ->and($data->dateUntil)->toBe('2024-12-31')
        ->and($data->search)->toBe('hello');
});

it('reads nested filters.* params from request', function (): void {
    $request = request()->replace([
        'filters' => [
            'log_name' => 'custom',
            'event'    => 'updated',
            'search'   => 'world',
        ],
    ]);
    app()->instance('request', $request);

    $data = ActivityLogFilterData::fromRequest();

    expect($data->logName)->toBe('custom')
        ->and($data->event)->toBe('updated')
        ->and($data->search)->toBe('world');
});

it('returns null for absent params', function (): void {
    $request = request()->replace([]);
    app()->instance('request', $request);

    $data = ActivityLogFilterData::fromRequest();

    expect($data->logName)->toBeNull()
        ->and($data->event)->toBeNull()
        ->and($data->search)->toBeNull();
});

it('reads search from query param as fallback', function (): void {
    $request = request()->replace(['query' => 'find me']);
    app()->instance('request', $request);

    $data = ActivityLogFilterData::fromRequest();

    expect($data->search)->toBe('find me');
});

it('prefers direct param over nested param', function (): void {
    $request = request()->replace([
        'event'   => 'created',
        'filters' => ['event' => 'deleted'],
    ]);
    app()->instance('request', $request);

    $data = ActivityLogFilterData::fromRequest();

    expect($data->event)->toBe('created');
});

it('returns predefined filter request keys', function (): void {
    expect(ActivityLogFilterData::filterRequestKeys())->toBe([
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

it('builds active filter chips with translated event value', function (): void {
    $request = request()->replace([
        'event' => 'created',
        'log_name' => 'default',
    ]);
    app()->instance('request', $request);

    $data = ActivityLogFilterData::fromRequest();
    $chips = $data->activeFilterChips();

    expect($chips)->toHaveCount(2)
        ->and($chips[0])->toMatchArray([
            'requestKey' => 'log_name',
            'label' => (string) __('moontrail::ui.field_log'),
            'value' => 'default',
        ])
        ->and($chips[1])->toMatchArray([
            'requestKey' => 'event',
            'label' => (string) __('moontrail::ui.field_event'),
            'value' => (string) __('moontrail::ui.event_created'),
        ]);
});
