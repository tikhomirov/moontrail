<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;

final class TestCustomActivity extends Model implements ModelBackedActivityRecordContract
{
    public $timestamps = false;

    protected $table = 'custom_activity_logs';

    protected $fillable = [
        'log_name',
        'subject_type',
        'subject_id',
        'event',
        'properties',
        'causer_type',
        'causer_id',
        'description',
        'created_at',
    ];

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    /** @return MorphTo<Model, $this> */
    public function causer(): MorphTo
    {
        return $this->morphTo('causer', 'causer_type', 'causer_id');
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
        $value = $this->subject_type;

        return is_string($value) && $value !== '' ? $value : null;
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

    public function model(): self
    {
        return $this;
    }

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
