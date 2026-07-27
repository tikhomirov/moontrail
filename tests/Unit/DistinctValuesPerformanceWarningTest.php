<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;

it('loads filter options from database when strategy is database_distinct', function (): void {
    config()->set('moontrail.filter_options.strategy', 'database_distinct');

    MoonTrailActivity::query()->create(['log_name' => 'alpha', 'event' => 'created', 'model_type' => 'Test', 'model_id' => 1]);
    MoonTrailActivity::query()->create(['log_name' => 'beta', 'event' => 'updated', 'model_type' => 'Test', 'model_id' => 2]);

    $query = new class implements ActivityQueryContract
    {
        public int $distinctCalls = 0;

        public function paginate(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
        {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
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

            return MoonTrailActivity::query()
                ->distinct()
                ->whereNotNull($column)
                ->pluck($column)
                ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
                ->values()
                ->all();
        }
    };

    app()->instance(ActivityQueryContract::class, $query);
    app()->forgetInstance(ActivityLogFilterOptions::class);

    $options = app(ActivityLogFilterOptions::class);

    expect($options->logNames())->toBe(['alpha' => 'alpha', 'beta' => 'beta'])
        ->and($query->distinctCalls)->toBe(1);
});

it('uses static filter options without running distinct query', function (): void {
    config()->set('moontrail.filter_options.strategy', 'static');
    config()->set('moontrail.filter_options.static.log_names', ['static-log']);
    config()->set('moontrail.filter_options.static.events', ['created', 'updated']);

    $query = new class implements ActivityQueryContract
    {
        public int $distinctCalls = 0;

        public function paginate(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
        {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1);
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

            return [];
        }
    };

    app()->instance(ActivityQueryContract::class, $query);
    app()->forgetInstance(ActivityLogFilterOptions::class);

    $options = app(ActivityLogFilterOptions::class);

    expect($options->logNames())->toBe(['static-log' => 'static-log'])
        ->and($options->events())->toBe(['created' => 'created', 'updated' => 'updated'])
        ->and($query->distinctCalls)->toBe(0);
});
