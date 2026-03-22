<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MoonTrail\Contracts\ActivityFilterOptionsContract;

/**
 * Builds UI-ready filter options from configured provider strategy.
 */
final readonly class ActivityLogFilterOptions
{
    public function __construct(private ActivityFilterOptionsContract $provider) {}

    /**
     * @return array<string, string>
     */
    public function logNames(): array
    {
        return $this->buildOptions(
            $this->provider->logNames(),
            false,
        );
    }

    /**
     * @return array<string, string>
     */
    public function events(): array
    {
        return $this->buildOptions(
            $this->provider->events(),
            false,
        );
    }

    /**
     * @return array<string, string>
     */
    public function subjectTypes(): array
    {
        return $this->buildOptions(
            $this->provider->subjectTypes(),
            true,
        );
    }

    /**
     * @return array<string, string>
     */
    public function causerTypes(): array
    {
        return $this->buildOptions(
            $this->provider->causerTypes(),
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
