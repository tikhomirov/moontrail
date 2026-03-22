<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Activity;

use DateTimeInterface;
use Illuminate\Support\Collection;
use MoonShine\MoonTrail\Contracts\ModelBackedActivityRecordContract;
use Spatie\Activitylog\Models\Activity;

final readonly class SpatieActivityAdapter implements ModelBackedActivityRecordContract
{
    public function __construct(
        private Activity $activity,
    ) {}

    public function model(): \Spatie\Activitylog\Models\Activity
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
        $properties = $this->activity->properties;

        if ($properties instanceof Collection) {
            /** @var array<string, mixed> $values */
            $values = $properties->toArray();

            return $values;
        }

        /** @var array<string, mixed>|null $properties */
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
        $value = (string) $this->activity->causer_type;

        return $value !== '' ? $value : null;
    }

    public function getCauserId(): mixed
    {
        return $this->activity->causer_id;
    }

    public function getDescription(): ?string
    {
        $value = (string) $this->activity->description;

        return $value !== '' ? $value : null;
    }
}
