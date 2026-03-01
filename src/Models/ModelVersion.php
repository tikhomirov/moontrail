<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity;

/**
 * @property int $id
 * @property string $versionable_type
 * @property int $versionable_id
 * @property int $version
 * @property array<string, mixed> $snapshot
 * @property int|null $activity_id
 * @property string|null $author_type
 * @property int|null $author_id
 * @property string $event
 * @property bool $is_rollback
 * @property int|null $rollback_to_version
 *
 * @method static Builder<self> query()
 */
final class ModelVersion extends Model
{
    protected $table = 'model_versions';

    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version',
        'snapshot',
        'activity_id',
        'author_type',
        'author_id',
        'event',
        'is_rollback',
        'rollback_to_version',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'snapshot'            => 'array',
        'is_rollback'         => 'boolean',
        'version'             => 'integer',
        'rollback_to_version' => 'integer',
    ];

    /** @return MorphTo<Model, $this> */
    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey());
    }
}
