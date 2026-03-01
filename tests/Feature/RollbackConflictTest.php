<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Exceptions\RollbackCancelledException;
use MoonShine\MoonTrail\Tests\Fixtures\AllowRollbackPolicy;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;
use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('returns 409 when RollbackConflictException is thrown', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $post = TestPost::query()->create(['name' => 'Original', 'body' => 'Body']);
    $post->update(['name' => 'Changed']);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Conflict Admin',
        'email' => 'conflict-admin@example.test',
    ]);

    // Force a DB error inside the transaction by dropping the table in the handler
    \Illuminate\Support\Facades\Event::listen(
        \MoonShine\MoonTrail\Events\ModelRollingBack::class,
        static function (): void {
            // after this listener returns, the service will try to run the transaction
        },
    );

    // Simulate a DB constraint by overriding the service with one that throws
    $this->app->bind(
        \MoonShine\MoonTrail\Contracts\RollbackStrategyContract::class,
        static fn (): object => new class($post) implements \MoonShine\MoonTrail\Contracts\RollbackStrategyContract
        {
            public function rollback(
                \Illuminate\Database\Eloquent\Model $model,
                int $targetVersion,
                ?array $rules = null,
            ): \Illuminate\Database\Eloquent\Model {
                throw new \MoonShine\MoonTrail\Exceptions\RollbackConflictException(
                    'Unique constraint violation',
                    0,
                    null,
                    'db_constraint',
                );
            }
        },
    );

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertStatus(409)
        ->assertSessionHas('toast.message', __('moontrail::ui.rollback_error_conflict'));
});

it('returns 422 when RollbackCancelledException is thrown', function (): void {
    Gate::policy(TestPost::class, AllowRollbackPolicy::class);

    $post = TestPost::query()->create(['name' => 'Original', 'body' => 'Body']);
    $post->update(['name' => 'Changed']);

    $target = $post->versions()->where('version', 1)->firstOrFail();

    $admin = TestAdmin::query()->create([
        'name'  => 'Cancel Admin',
        'email' => 'cancel-admin@example.test',
    ]);

    $this->app->bind(
        \MoonShine\MoonTrail\Contracts\RollbackStrategyContract::class,
        static fn (): object => new class implements \MoonShine\MoonTrail\Contracts\RollbackStrategyContract
        {
            public function rollback(
                \Illuminate\Database\Eloquent\Model $model,
                int $targetVersion,
                ?array $rules = null,
            ): \Illuminate\Database\Eloquent\Model {
                throw new RollbackCancelledException('Cancelled by listener.');
            }
        },
    );

    $this->actingAs($admin, MoonShineAuth::getGuardName())
        ->withoutMiddleware()
        ->post(route('moonshine.moontrail.rollback'), [
            'modelVersion' => $target->id,
        ])
        ->assertStatus(422)
        ->assertSessionHas('toast.message', __('moontrail::ui.rollback_error_cancelled'));
});
