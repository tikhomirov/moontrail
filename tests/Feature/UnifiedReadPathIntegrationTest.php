<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Http\Controllers\ActivityController;
use MoonShine\MoonTrail\Pages\MoonTrailIndexPage;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogFilterOptions;
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

    $query = new class implements ActivityQueryContract
    {
        public int $paginateCalls = 0;

        public int $findCalls = 0;

        public int $statsCalls = 0;

        public int $distinctCalls = 0;

        public int $modelClassCalls = 0;

        public function paginate(array $filters): LengthAwarePaginator
        {
            $this->paginateCalls++;
            $filterData = ActivityLogFilterData::fromArray($filters);

            return (new ActivityLogQuery)
                ->apply(TestCustomActivity::query()->latest('id'), $filterData)
                ->paginate(20);
        }

        public function find(int|string $id): ?ModelBackedActivityRecordContract
        {
            $this->findCalls++;
            $record = TestCustomActivity::query()->find($id);

            return $record instanceof TestCustomActivity ? $record : null;
        }

        public function stats(): array
        {
            $this->statsCalls++;

            return ['total' => 1, 'created' => 1, 'updated' => 0, 'deleted' => 0, 'other' => 0];
        }

        public function modelClass(): string
        {
            $this->modelClassCalls++;

            return TestCustomActivity::class;
        }

        public function distinctValues(string $column): array
        {
            $this->distinctCalls++;

            return $column === 'log_name' ? ['custom'] : [];
        }
    };

    app()->instance(ActivityQueryContract::class, $query);
});

it('uses one query contract across resource controller page and filter options', function (): void {
    $activity = TestCustomActivity::query()->create([
        'log_name'    => 'custom',
        'event'       => 'created',
        'properties'  => ['old' => [], 'attributes' => ['name' => 'A']],
        'description' => 'created',
        'created_at'  => now(),
    ]);

    $query = app(ActivityQueryContract::class);

    $resource = app(MoonTrailResource::class);
    $items = $resource->getItems();

    expect($items)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and(data_get($query, 'paginateCalls'))->toBe(1);

    $resource->setItem($activity);
    $resource->findItem();

    expect(data_get($query, 'findCalls'))->toBe(1);

    $controller = app(ActivityController::class);
    $controller->diff($activity->id);

    expect(data_get($query, 'findCalls'))->toBe(2);

    $page = app(MoonTrailIndexPage::class);
    $method = new \ReflectionMethod($page, 'renderKpiBlock');
    $method->invoke($page);

    expect(data_get($query, 'statsCalls'))->toBe(1);

    $filterOptions = app(ActivityLogFilterOptions::class);
    $options = $filterOptions->logNames();

    expect($options)->toBe(['custom' => 'custom'])
        ->and(data_get($query, 'distinctCalls'))->toBe(1)
        ->and(data_get($query, 'modelClassCalls'))->toBe(0);
});
