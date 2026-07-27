<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use MoonShine\MoonTrail\Contracts\ActivityLoggerContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Tests\Fixtures\TestCustomActivity;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;
use Spatie\Activitylog\Models\Activity;

$resetDriverBindings = static function (): void {
    app()->forgetInstance(ActivityLoggerContract::class);
    app()->forgetInstance(ActivityQueryContract::class);
};

it('supports e2e flow in spatie mode', function () use ($resetDriverBindings): void {
    config()->set('moontrail.activity_logger', 'spatie');
    $resetDriverBindings();

    $post = TestPost::query()->create(['name' => 'Before', 'body' => 'Body']);
    $post->update(['name' => 'After']);

    $activity = Activity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $query = app(ActivityQueryContract::class);
    $items = $query->paginate([]);

    expect($items)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($items->total())->toBeGreaterThan(0)
        ->and($query->stats()['total'])->toBeGreaterThan(0);

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk()
        ->assertJsonPath('event', 'updated');
});

it('supports e2e flow in database mode', function () use ($resetDriverBindings): void {
    config()->set('moontrail.activity_logger', 'database');
    $resetDriverBindings();

    $post = TestPost::query()->create(['name' => 'Before', 'body' => 'Body']);
    $post->update(['name' => 'After']);

    $activity = MoonTrailActivity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $query = app(ActivityQueryContract::class);
    $items = $query->paginate([]);

    expect($items)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($items->total())->toBeGreaterThan(0)
        ->and($query->stats()['total'])->toBeGreaterThan(0);

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk()
        ->assertJsonPath('event', 'updated');
});

it('supports e2e flow in custom mode', function () use ($resetDriverBindings): void {
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
    $resetDriverBindings();

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

    app()->instance(ActivityQueryContract::class, new class implements ActivityQueryContract
    {
        public function paginate(array $filters): LengthAwarePaginator
        {
            return TestCustomActivity::query()->latest('id')->paginate(20);
        }

        public function find(int|string $id): ?TestCustomActivity
        {
            $record = TestCustomActivity::query()->find($id);

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
            return TestCustomActivity::class;
        }

        public function distinctValues(string $column): array
        {
            return TestCustomActivity::query()
                ->distinct()
                ->whereNotNull($column)
                ->pluck($column)
                ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
                ->values()
                ->all();
        }
    });

    $post = TestPost::query()->create(['name' => 'Before', 'body' => 'Body']);
    $post->update(['name' => 'After']);

    $activity = TestCustomActivity::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->latest('id')
        ->firstOrFail();

    $query = app(ActivityQueryContract::class);
    $items = $query->paginate([]);

    expect($items)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($items->total())->toBeGreaterThan(0)
        ->and($query->stats()['total'])->toBeGreaterThan(0);

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => $activity->id]))
        ->assertOk()
        ->assertJsonPath('event', 'updated');
});

it('does not crash in none mode and returns empty query results', function () use ($resetDriverBindings): void {
    config()->set('moontrail.activity_logger', 'none');
    $resetDriverBindings();

    $post = TestPost::query()->create(['name' => 'Before', 'body' => 'Body']);
    $post->update(['name' => 'After']);

    expect(Activity::query()->count())->toBe(0)
        ->and(MoonTrailActivity::query()->count())->toBe(0);

    $query = app(ActivityQueryContract::class);
    $items = $query->paginate([]);

    expect($items)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($items->total())->toBe(0)
        ->and($query->stats()['total'])->toBe(0)
        ->and($query->distinctValues('event'))->toBe([]);

    $this->withoutMiddleware()
        ->get(route('moonshine.moontrail.diff', ['activity' => 1]))
        ->assertNotFound();
});
