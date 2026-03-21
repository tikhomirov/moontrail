<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Spatie\Activitylog\Models\Activity;

/**
 * Collects distinct filter option values from the activity_log table.
 */
final class ActivityLogFilterOptions
{
    /**
     * @return array<string, string>
     */
    public function logNames(): array
    {
        return $this->buildOptions(
            Activity::query()->distinct()->whereNotNull('log_name')->pluck('log_name')->all(),
            false,
        );
    }

    /**
     * @return array<string, string>
     */
    public function events(): array
    {
        return $this->buildOptions(
            Activity::query()->distinct()->whereNotNull('event')->pluck('event')->all(),
            false,
        );
    }

    /**
     * @return array<string, string>
     */
    public function subjectTypes(): array
    {
        return $this->buildOptions(
            Activity::query()->distinct()->whereNotNull('subject_type')->pluck('subject_type')->all(),
            true,
        );
    }

    /**
     * @return array<string, string>
     */
    public function causerTypes(): array
    {
        return $this->buildOptions(
            Activity::query()->distinct()->whereNotNull('causer_type')->pluck('causer_type')->all(),
            true,
        );
    }

    /**
     * @param array<mixed> $values
     * @return array<string, string>
     */
    private function buildOptions(array $values, bool $useClassBasename): array
    {
        $options = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            if ($value === '') {
                continue;
            }
            $options[$value] = $useClassBasename ? class_basename($value) : $value;
        }

        return $options;
    }
}
