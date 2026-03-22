<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Contracts\ActivityFilterOptionsContract;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use RuntimeException;

final readonly class ActivityFilterOptionsProvider implements ActivityFilterOptionsContract
{
    /**
     * @param ActivityQueryContract<Model> $query
     */
    public function __construct(
        private ActivityQueryContract $query,
    ) {}

    public function logNames(): array
    {
        return $this->resolveValues(staticKey: 'log_names', column: 'log_name');
    }

    public function events(): array
    {
        return $this->resolveValues(staticKey: 'events', column: 'event');
    }

    public function subjectTypes(): array
    {
        return $this->resolveValues(staticKey: 'subject_types', column: 'subject_type');
    }

    public function causerTypes(): array
    {
        return $this->resolveValues(staticKey: 'causer_types', column: 'causer_type');
    }

    /**
     * @return list<string>
     */
    private function resolveValues(string $staticKey, string $column): array
    {
        $strategy = config('moontrail.filter_options.strategy', 'database_distinct');

        if (! is_string($strategy) || $strategy === '') {
            $strategy = 'database_distinct';
        }

        if ($strategy === 'static') {
            $values = config("moontrail.filter_options.static.{$staticKey}", []);

            return $this->normalizeValues(is_array($values) ? $values : []);
        }

        if ($strategy !== 'database_distinct') {
            throw new RuntimeException('Unsupported moontrail.filter_options.strategy: ' . $strategy);
        }

        if ($column === '') {
            return [];
        }

        /** @var non-empty-string $column */
        return $this->normalizeValues($this->query->distinctValues($column));
    }

    /**
     * @param array<mixed> $values
     * @return list<string>
     */
    private function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            if ($value === '') {
                continue;
            }
            $normalized[] = $value;
        }

        /** @var list<string> $unique */
        $unique = array_values(array_unique($normalized));

        return $unique;
    }
}
