<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use DateTimeInterface;

interface ActivityRecordContract
{
    public function getId(): int|string;

    public function getEvent(): string;

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array;

    public function getCreatedAt(): DateTimeInterface;

    public function getSubjectType(): ?string;

    public function getSubjectId(): mixed;

    public function getCauserType(): ?string;

    public function getCauserId(): mixed;

    public function getDescription(): ?string;
}
