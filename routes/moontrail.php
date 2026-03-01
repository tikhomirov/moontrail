<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MoonShine\MoonTrail\Http\Controllers\ActivityController;
use MoonShine\MoonTrail\Http\Controllers\RollbackController;

Route::prefix(config('moonshine.prefix', 'admin') . '/moontrail')
    ->name('moonshine.moontrail.')
    ->middleware(array_merge(
        config('moonshine.middleware', []),
        config('moonshine.auth.middleware', []),
    ))
    ->group(static function (): void {
        Route::post('/rollback', RollbackController::class)
            ->name('rollback');

        Route::get('/{activity}/diff', [ActivityController::class, 'diff'])
            ->whereNumber('activity')
            ->name('diff');
    });
