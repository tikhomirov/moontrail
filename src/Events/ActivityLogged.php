<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Events;

use Illuminate\Database\Eloquent\Model;

final readonly class ActivityLogged
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public Model $model,
        public string $event,
        public array $payload = [],
        public ?int $activityId = null,
    ) {}
}
