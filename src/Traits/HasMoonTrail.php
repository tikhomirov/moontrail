<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Traits;

/**
 * Convenience trait that combines versioning and Spatie activity logging.
 *
 * Use HasMoonTrailVersioning alone if you don't need Spatie's LogsActivity.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMoonTrail
{
    use HasMoonTrailActivity;
    use HasMoonTrailVersioning;
}
