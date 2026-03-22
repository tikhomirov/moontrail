<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\MoonTrailObserver;

/**
 * Provides versioning capabilities (versions relation, excluded fields, rollback flag).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMoonTrailVersioning
{
    /**
     * @return MorphMany<ModelVersion, $this>
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(ModelVersion::class, 'versionable')
            ->orderByDesc('version');
    }

    /**
     * @return MorphOne<ModelVersion, $this>
     */
    public function latestVersion(): MorphOne
    {
        return $this->morphOne(ModelVersion::class, 'versionable')
            ->latestOfMany('version');
    }

    public function currentVersionNumber(): int
    {
        return (int) ($this->versions()->max('version') ?? 0);
    }

    /**
     * @return array<int, string>
     */
    public function getVersionExcludedFields(): array
    {
        return $this->versionExcludedFields();
    }

    public function isRollbackAllowed(): bool
    {
        return false;
    }

    protected static function bootHasMoonTrailVersioning(): void
    {
        static::observe(MoonTrailObserver::class);
    }

    /**
     * @return array<int, string>
     */
    protected function versionExcludedFields(): array
    {
        return [
            'password',
            'remember_token',
        ];
    }
}
