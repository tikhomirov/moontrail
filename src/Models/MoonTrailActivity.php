<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;

/**
 * Native activity log model — used when Spatie is not installed.
 *
 * @property int $id
 * @property string|null $log_name
 * @property string $subject_type
 * @property string|int $subject_id
 * @property string $model_type
 * @property string|int $model_id
 * @property string $event
 * @property array<string, mixed> $properties
 * @property string|null $causer_type
 * @property string|int|null $causer_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 */
final class MoonTrailActivity extends Model implements ActivityRecordContract
{
    public $timestamps = false;

    protected $table = 'moontrail_activity_log';

    protected $fillable = [
        'log_name',
        'subject_type',
        'subject_id',
        'model_type',
        'model_id',
        'event',
        'properties',
        'causer_type',
        'causer_id',
        'description',
        'created_at',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function causer(): MorphTo
    {
        return $this->morphTo('causer', 'causer_type', 'causer_id');
    }

    public function getSubjectTypeAttribute(?string $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $fallback = $this->attributes['model_type'] ?? null;

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    public function getSubjectIdAttribute(mixed $value): mixed
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->attributes['model_id'] ?? null;
    }

    public function setSubjectTypeAttribute(?string $value): void
    {
        $this->attributes['subject_type'] = $value;
        $this->attributes['model_type'] = $value;
    }

    public function setSubjectIdAttribute(mixed $value): void
    {
        $this->attributes['subject_id'] = $value;
        $this->attributes['model_id'] = $value;
    }

    public function getId(): int|string
    {
        $id = $this->getKey();

        return is_int($id) || is_string($id) ? $id : 0;
    }

    public function getEvent(): string
    {
        return (string) $this->event;
    }

    public function getProperties(): array
    {
        /** @var array<string, mixed>|null $properties */
        $properties = $this->properties;

        return $properties ?? [];
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at ?? now();
    }

    public function getSubjectType(): ?string
    {
        return $this->subject_type;
    }

    public function getSubjectId(): mixed
    {
        return $this->subject_id;
    }

    public function getCauserType(): ?string
    {
        $value = $this->causer_type;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getCauserId(): mixed
    {
        return $this->causer_id;
    }

    public function getDescription(): ?string
    {
        $value = (string) $this->description;

        return $value !== '' ? $value : null;
    }

    protected static function booted(): void
    {
        self::creating(static function (self $model): void {
            if ($model->created_at === null) {
                $model->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
