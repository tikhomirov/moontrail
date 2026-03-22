<?php

declare(strict_types=1);

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryUiContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('uses manually bound custom logger and query in custom mode', function (): void {
    config()->set('moontrail.activity_logger', 'custom');

    app()->singleton(ActivityLoggerContract::class, static fn (): ActivityLoggerContract => new class implements ActivityLoggerContract
    {
        public bool $called = false;

        public function log(Model $model, string $event, array $data = []): int
        {
            $this->called = true;

            return 777;
        }
    });

    app()->singleton(ActivityQueryContract::class, static fn (): ActivityQueryContract => new class implements ActivityQueryContract
    {
        public function paginate(array $filters): LengthAwarePaginator
        {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 1, 1);
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
            return [];
        }
    });

    $post = TestPost::query()->create(['name' => 'Custom']);

    $logger = app(ActivityLoggerContract::class);
    $result = $logger->log($post, 'created');

    expect($result)->toBe(777)
        ->and(app(ActivityQueryContract::class))->toBeInstanceOf(ActivityQueryContract::class);
});

it('does not require deprecated ui/model-backed query contracts in custom mode', function (): void {
    config()->set('moontrail.activity_logger', 'custom');

    app()->instance(ActivityQueryContract::class, new class implements ActivityQueryContract
    {
        public function paginate(array $filters): LengthAwarePaginator
        {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 1, 1);
        }

        public function find(int|string $id): ?\MoonShine\MoonTrail\Contracts\ActivityRecordContract
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
            return [];
        }
    });

    expect(static fn (): MoonTrailResource => app(MoonTrailResource::class))
        ->not->toThrow(RuntimeException::class);
});

it('keeps deprecated compatibility binding for activity query ui contract', function (): void {
    config()->set('moontrail.activity_logger', 'custom');

    app()->instance(ActivityQueryContract::class, new class implements ActivityQueryContract
    {
        public function paginate(array $filters): LengthAwarePaginator
        {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 1, 1);
        }

        public function find(int|string $id): ?\MoonShine\MoonTrail\Contracts\ActivityRecordContract
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
            return [];
        }
    });

    expect(static fn (): ActivityQueryUiContract => app(ActivityQueryUiContract::class))
        ->toThrow(RuntimeException::class, 'ActivityQueryUiContract is deprecated compatibility layer');
});
