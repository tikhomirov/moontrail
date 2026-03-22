<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Activity;

use DateTimeInterface;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;

final readonly class DatabaseActivityAdapter implements ModelBackedActivityRecordContract
{
    public function __construct(
        private MoonTrailActivity $activity,
    ) {}

    public function model(): \MoonShine\MoonTrail\Models\MoonTrailActivity
    {
        return $this->activity;
    }

    public function getId(): int|string
    {
        $id = $this->activity->getKey();

        return is_int($id) || is_string($id) ? $id : 0;
    }

    public function getEvent(): string
    {
        return (string) $this->activity->event;
    }

    public function getProperties(): array
    {
        /** @var array<string, mixed>|null $properties */
        $properties = $this->activity->properties;

        return $properties ?? [];
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->activity->created_at ?? now();
    }

    public function getSubjectType(): ?string
    {
        $value = $this->activity->subject_type;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getSubjectId(): mixed
    {
        return $this->activity->subject_id;
    }

    public function getCauserType(): ?string
    {
        $value = $this->activity->causer_type;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getCauserId(): mixed
    {
        return $this->activity->causer_id;
    }

    public function getDescription(): ?string
    {
        $value = $this->activity->description;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
