<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Unit;

use MoonShine\MoonTrail\Support\ActivityLogFilterUrlBuilder;

it('removeFilterUrl removes specific filter from query string', function (): void {
    $request = request()->create('http://localhost/admin/moontrail', 'GET', [
        'event'    => 'created',
        'log_name' => 'default',
    ]);
    app()->instance('request', $request);

    $baseUrl = 'http://localhost/admin/moontrail';
    $builder = new ActivityLogFilterUrlBuilder($baseUrl);

    $url = $builder->removeFilterUrl('event');

    expect($url)->toContain('log_name=default')
        ->not()->toContain('event=');
});

it('removeFilterUrl preserves other params', function (): void {
    $request = request()->create('http://localhost/admin/moontrail', 'GET', [
        'event'        => 'created',
        'log_name'     => 'default',
        'subject_type' => 'App\\Post',
        'other_param'  => 'keep',
    ]);
    app()->instance('request', $request);

    $baseUrl = 'http://localhost/admin/moontrail';
    $builder = new ActivityLogFilterUrlBuilder($baseUrl);

    $url = $builder->removeFilterUrl('event');

    expect($url)->toContain('log_name=default')
        ->toContain('subject_type=')
        ->toContain('other_param=keep')
        ->not()->toContain('event=');
});

it('clearAllFiltersUrl removes all filter keys', function (): void {
    $request = request()->create('http://localhost/admin/moontrail', 'GET', [
        'event'        => 'created',
        'log_name'     => 'default',
        'subject_type' => 'App\\Post',
        'other_param'  => 'keep',
    ]);
    app()->instance('request', $request);

    $baseUrl = 'http://localhost/admin/moontrail';
    $builder = new ActivityLogFilterUrlBuilder($baseUrl);

    $filterKeys = ['event', 'log_name', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'date_from', 'date_until'];
    $url = $builder->clearAllFiltersUrl($filterKeys);

    expect($url)
        ->toContain('other_param=keep')
        ->not()->toContain('event=')
        ->not()->toContain('log_name=')
        ->not()->toContain('subject_type=');
});

it('eventFilterUrl sets event filter when provided', function (): void {
    $request = request()->create('http://localhost/admin/moontrail', 'GET', [
        'log_name' => 'default',
    ]);
    app()->instance('request', $request);

    $baseUrl = 'http://localhost/admin/moontrail';
    $builder = new ActivityLogFilterUrlBuilder($baseUrl);

    $url = $builder->eventFilterUrl('created');

    expect($url)->toContain('event=created')
        ->toContain('log_name=default');
});

it('eventFilterUrl removes event filter when null provided', function (): void {
    $request = request()->create('http://localhost/admin/moontrail', 'GET', [
        'event'    => 'created',
        'log_name' => 'default',
    ]);
    app()->instance('request', $request);

    $baseUrl = 'http://localhost/admin/moontrail';
    $builder = new ActivityLogFilterUrlBuilder($baseUrl);

    $url = $builder->eventFilterUrl(null);

    expect($url)->not()->toContain('event=')
        ->toContain('log_name=default');
});

it('eventFilterUrl removes nested filters event and keeps other nested filters', function (): void {
    $request = request()->create('http://localhost/admin/moontrail', 'GET', [
        'filters' => [
            'event' => 'deleted',
            'subject_type' => 'App\\Post',
        ],
    ]);
    app()->instance('request', $request);

    $builder = new ActivityLogFilterUrlBuilder('http://localhost/admin/moontrail');

    $url = $builder->eventFilterUrl('created');

    expect($url)
        ->toContain('event=created')
        ->toContain('filters%5Bsubject_type%5D=')
        ->not()->toContain('filters%5Bevent%5D');
});
