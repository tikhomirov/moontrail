<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Resources\MoonTrailResource;
use MoonShine\MoonTrail\Support\ActivityLogFilterData;
use MoonShine\MoonTrail\Support\ActivityLogQuery;
use MoonShine\MoonTrail\Tests\Fixtures\TestCustomActivity;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

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
        public bool $paginateCalled = false;

        public int $modelClassCalls = 0;

        public function paginate(array $filters): LengthAwarePaginator
        {
            $this->paginateCalled = true;
            $filterData = ActivityLogFilterData::fromArray($filters);
            $perPage = 20;
            $currentPage = request()->integer('page', 1);

            $builder = (new ActivityLogQuery)->apply(TestCustomActivity::query(), $filterData)
                ->with(['subject', 'causer'])
                ->latest('id');

            return $builder->paginate($perPage, page: $currentPage);
        }

        public function find(int|string $id): ?ModelBackedActivityRecordContract
        {
            $record = TestCustomActivity::query()->with(['subject', 'causer'])->find($id);

            return $record instanceof TestCustomActivity ? $record : null;
        }

        public function stats(): array
        {
            $counts = TestCustomActivity::query()->selectRaw('event, count(*) as cnt')->groupBy('event')->pluck('cnt', 'event')->all();
            $created = (int) ($counts['created'] ?? 0);
            $updated = (int) ($counts['updated'] ?? 0);
            $deleted = (int) ($counts['deleted'] ?? 0);
            $total = array_sum($counts);

            return [
                'total'   => $total,
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
                'other'   => max(0, $total - $created - $updated - $deleted),
            ];
        }

        public function modelClass(): string
        {
            $this->modelClassCalls++;

            return TestCustomActivity::class;
        }

        public function distinctValues(string $column): array
        {
            /** @var list<string> $values */
            $values = TestCustomActivity::query()->distinct()->whereNotNull($column)->pluck($column)->filter(
                static fn (mixed $value): bool => is_string($value) && $value !== '',
            )->values()->all();

            return $values;
        }
    };

    app()->instance(ActivityQueryContract::class, $query);

    app()->instance(ActivityLoggerContract::class, new class implements ActivityLoggerContract
    {
        public function log(Model $model, string $event, array $data = []): int
        {
            $record = TestCustomActivity::query()->create([
                'log_name'     => 'custom',
                'subject_type' => $model->getMorphClass(),
                'subject_id'   => $model->getKey(),
                'event'        => $event,
                'properties'   => [
                    'old'        => $data['old'] ?? [],
                    'attributes' => $data['attributes'] ?? [],
                ],
                'causer_type' => null,
                'causer_id'   => null,
                'description' => $data['description'] ?? 'custom',
                'created_at'  => now(),
            ]);

            return (int) $record->getKey();
        }
    });
});

it('supports custom mode full flow and resource uses query contract paginator', function (): void {
    $post = TestPost::query()->create(['name' => 'Old', 'body' => 'Body']);
    $post->update(['name' => 'New']);

    $activity = TestCustomActivity::query()->latest('id')->firstOrFail();

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk()
        ->assertJsonPath('event', 'updated');

    $resource = app(MoonTrailResource::class);
    $items = $resource->getItems();

    expect($items)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and(app(ActivityQueryContract::class))->toBeInstanceOf(ActivityQueryContract::class)
        ->and(app(ActivityQueryContract::class)->paginateCalled)->toBeTrue()
        ->and(app(ActivityQueryContract::class)->modelClassCalls)->toBe(0);
});

it('throws explicit error on detail page when custom query returns non model-backed record', function (): void {
    app()->forgetInstance(ActivityQueryContract::class);
    app()->forgetInstance(MoonTrailResource::class);

    $query = new class implements ActivityQueryContract
    {
        public function paginate(array $filters): LengthAwarePaginator
        {
            return new LengthAwarePaginator([], 0, 20, 1);
        }

        public function find(int|string $id): \MoonShine\MoonTrail\Contracts\ActivityRecordContract
        {
            return new class implements ActivityRecordContract
            {
                public function getId(): int
                {
                    return 999;
                }

                public function getEvent(): string
                {
                    return 'updated';
                }

                public function getProperties(): array
                {
                    return [];
                }

                public function getCreatedAt(): \DateTimeInterface
                {
                    return now();
                }

                public function getSubjectType(): ?string
                {
                    return null;
                }

                public function getSubjectId(): mixed
                {
                    return null;
                }

                public function getCauserType(): ?string
                {
                    return null;
                }

                public function getCauserId(): mixed
                {
                    return null;
                }

                public function getDescription(): ?string
                {
                    return null;
                }
            };
        }

        public function stats(): array
        {
            return ['total' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'other' => 0];
        }

        public function modelClass(): string
        {
            return TestCustomActivity::class;
        }

        public function distinctValues(string $column): array
        {
            return [];
        }
    };

    app()->instance(ActivityQueryContract::class, $query);

    $resource = app(MoonTrailResource::class);
    $resource->setItem(new TestCustomActivity(['id' => 999]));

    expect(static fn (): mixed => $resource->findItem(orFail: true))
        ->toThrow(RuntimeException::class, 'requires MoonShine\\MoonTrail\\Contracts\\ModelBackedActivityRecordContract');
});
