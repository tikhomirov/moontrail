<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryUiContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogQuery;
use MoonShine\MoonTrail\Tests\Fixtures\TestCustomActivity;

beforeEach(function (): void {
    Schema::dropIfExists('custom_activity_logs');
    Schema::create('custom_activity_logs', static function (Blueprint $table): void {
        $table->id();
        $table->string('log_name')->nullable();
        $table->string('subject_type')->nullable();
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->string('event');
        $table->json('properties')->nullable();
        $table->string('causer_type')->nullable();
        $table->unsignedBigInteger('causer_id')->nullable();
        $table->text('description')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    config()->set('moontrail.activity_logger', 'custom');
    config()->set('moontrail.activity_model', TestCustomActivity::class);
    config()->set('moontrail.pruning.retention_days', 5);

    $query = new class implements ActivityQueryUiContract, ModelBackedActivityQueryContract
    {
        public int $modelClassCalls = 0;

        public function paginate(array $filters): LengthAwarePaginator
        {
            $filterData = ActivityLogFilterData::fromArray($filters);

            return (new ActivityLogQuery)
                ->apply(TestCustomActivity::query(), $filterData)
                ->latest('id')
                ->paginate(20);
        }

        public function find(int|string $id): ?ModelBackedActivityRecordContract
        {
            $record = TestCustomActivity::query()->find($id);

            return $record instanceof TestCustomActivity ? $record : null;
        }

        public function stats(): array
        {
            return ['total' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'other' => 0];
        }

        public function modelClass(): string
        {
            $this->modelClassCalls++;

            return TestCustomActivity::class;
        }

        public function distinctValues(string $column): array
        {
            return [];
        }
    };

    app()->instance(ActivityQueryContract::class, $query);
    app()->instance(ActivityQueryUiContract::class, $query);
    app()->instance(ModelBackedActivityQueryContract::class, $query);
});

it('uses pruning retention_days config when --days is omitted in custom mode', function (): void {
    TestCustomActivity::query()->create([
        'log_name'     => 'custom',
        'subject_type' => 'App\\Models\\Post',
        'subject_id'   => 1,
        'event'        => 'updated',
        'properties'   => ['old' => [], 'attributes' => []],
        'description'  => 'old',
        'created_at'   => now()->subDays(10),
    ]);

    TestCustomActivity::query()->create([
        'log_name'     => 'custom',
        'subject_type' => 'App\\Models\\Post',
        'subject_id'   => 2,
        'event'        => 'updated',
        'properties'   => ['old' => [], 'attributes' => []],
        'description'  => 'new',
        'created_at'   => now()->subDays(1),
    ]);

    Artisan::call('moontrail:prune', ['--activity-only' => true]);

    $query = app(ActivityQueryContract::class);

    expect(TestCustomActivity::query()->count())->toBe(1)
        ->and(TestCustomActivity::query()->first()?->description)->toBe('new')
        ->and($query->modelClassCalls)->toBe(0);
});
