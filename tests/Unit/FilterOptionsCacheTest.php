<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;

it('does not cache distinct filter values when cache is disabled', function (): void {
    config()->set('moontrail.filters.source', 'database_distinct');
    config()->set('moontrail.filters.cache.enabled', false);
    config()->set('moontrail.filters.cache.ttl', 60);
    Cache::flush();

    $query = new class implements ActivityQueryContract
    {
        public int $distinctCalls = 0;

        public function paginate(array $filters): LengthAwarePaginator
        {
            return new LengthAwarePaginator([], 0, 20, 1);
        }

        public function find(int|string $id): ?ActivityRecordContract
        {
            return null;
        }

        public function stats(): array
        {
            return ['total' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'other' => 0];
        }

        public function modelClass(): string
        {
            return MoonTrailActivity::class;
        }

        public function distinctValues(string $column): array
        {
            $this->distinctCalls++;

            return ['alpha', 'beta'];
        }
    };

    app()->instance(ActivityQueryContract::class, $query);
    app()->forgetInstance(ActivityLogFilterOptions::class);

    $options = app(ActivityLogFilterOptions::class);

    expect($options->logNames())->toBe(['alpha' => 'alpha', 'beta' => 'beta'])
        ->and($options->logNames())->toBe(['alpha' => 'alpha', 'beta' => 'beta'])
        ->and($query->distinctCalls)->toBe(2);
});

it('caches distinct filter values when cache is enabled', function (): void {
    config()->set('moontrail.filters.source', 'database_distinct');
    config()->set('moontrail.filters.cache.enabled', true);
    config()->set('moontrail.filters.cache.ttl', 120);
    Cache::flush();

    $query = new class implements ActivityQueryContract
    {
        public int $distinctCalls = 0;

        public function paginate(array $filters): LengthAwarePaginator
        {
            return new LengthAwarePaginator([], 0, 20, 1);
        }

        public function find(int|string $id): ?ActivityRecordContract
        {
            return null;
        }

        public function stats(): array
        {
            return ['total' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'other' => 0];
        }

        public function modelClass(): string
        {
            return MoonTrailActivity::class;
        }

        public function distinctValues(string $column): array
        {
            $this->distinctCalls++;

            return ['alpha', 'beta'];
        }
    };

    app()->instance(ActivityQueryContract::class, $query);
    app()->forgetInstance(ActivityLogFilterOptions::class);

    $options = app(ActivityLogFilterOptions::class);

    expect($options->logNames())->toBe(['alpha' => 'alpha', 'beta' => 'beta'])
        ->and($options->logNames())->toBe(['alpha' => 'alpha', 'beta' => 'beta'])
        ->and($query->distinctCalls)->toBe(1);
});
