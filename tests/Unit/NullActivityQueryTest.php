<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use MoonShine\MoonTrail\Activity\NullActivityQuery;

it('returns safe empty paginator with configured per-page', function (): void {
    config()->set('moontrail.ui.per_page', 37);

    $query = new NullActivityQuery;
    $paginator = $query->paginate([]);

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->total())->toBe(0)
        ->and($paginator->perPage())->toBe(37)
        ->and($paginator->items())->toBeArray()->toHaveCount(0);
});

it('returns safe empty results for all read operations', function (): void {
    $query = new NullActivityQuery;

    expect($query->find(1))->toBeNull()
        ->and($query->distinctValues('event'))->toBe([])
        ->and($query->stats())->toBe([
            'total'   => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'other'   => 0,
        ]);
});
