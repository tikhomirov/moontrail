<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Traits;

use RuntimeException;
use Spatie\Activitylog\LogOptions;

/**
 * Provides Spatie activity log integration (LogsActivity + default options).
 * Optional — only useful when using the Spatie logger backend.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMoonTrailActivity
{
    public function getActivitylogOptions(): LogOptions
    {
        $this->ensureSpatieInstalled();

        return $this->activityLogOptions();
    }

    protected static function bootHasMoonTrailActivity(): void
    {
        if (! trait_exists(\Spatie\Activitylog\Traits\LogsActivity::class)) {
            throw new RuntimeException(
                'HasMoonTrailActivity requires spatie/laravel-activitylog. Use HasMoonTrailVersioning instead.',
            );
        }
    }

    protected function activityLogOptions(): LogOptions
    {
        $this->ensureSpatieInstalled();

        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $event): string => class_basename(static::class) . " was {$event}",
            );
    }

    private function ensureSpatieInstalled(): void
    {
        if (! class_exists(\Spatie\Activitylog\LogOptions::class)) {
            throw new RuntimeException(
                'HasMoonTrailActivity requires spatie/laravel-activitylog. Use HasMoonTrailVersioning instead.',
            );
        }
    }
}
