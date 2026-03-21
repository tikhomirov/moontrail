<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Models\ModelVersion;

/**
 * Builds computed display data for the ActivityTimeline component.
 */
final class ActivityTimelineDataBuilder
{
    /**
     * Returns a map of version.id → number of changed fields (non-unchanged).
     * Uses eager-loaded activity relation to avoid N+1.
     *
     * @param list<ModelVersion> $versions
     * @return array<int, int>
     */
    public function computeChangesCount(array $versions): array
    {
        $result = [];

        foreach ($versions as $version) {
            $activity = $version->relationLoaded('activity') ? $version->activity : null;

            if ($activity === null && $version->activity_id !== null) {
                $activity = $version->activity()->first();
            }

            if ($activity === null) {
                $result[$version->id] = 0;

                continue;
            }

            $changes = DiffComputer::fromActivity($activity);
            $result[$version->id] = count(array_filter(
                $changes,
                static fn (FieldChange $c): bool => $c->type->value !== 'unchanged',
            ));
        }

        return $result;
    }
}
