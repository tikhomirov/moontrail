<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use MoonShine\MoonTrail\Models\ModelVersion;

/**
 * Minimal model without HasMoonTrail — simulates auto_track_models behaviour.
 * Has versions() so shouldVersion() returns true in the observer.
 */
final class TestAutoTrackedPost extends Model
{
    protected $table = 'test_posts';

    protected $fillable = [
        'name',
        'body',
    ];

    /** @return MorphMany<ModelVersion, $this> */
    public function versions(): MorphMany
    {
        return $this->morphMany(ModelVersion::class, 'versionable')->orderByDesc('version');
    }
}
