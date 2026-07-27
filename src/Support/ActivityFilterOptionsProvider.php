<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
        $strategy = MoonTrailConfig::filterSource();

        if ($strategy === 'static') {
            $values = MoonTrailConfig::filterStaticValues($staticKey);

            return $this->normalizeValues($values);
        }

        if ($strategy !== 'database_distinct') {
            throw new RuntimeException('Unsupported moontrail.filter_options.strategy: ' . $strategy);
        }

        if ($column === '') {
            return [];
        }

        if (! $this->isCacheEnabled()) {
            /** @var non-empty-string $column */
            return $this->normalizeValues($this->query->distinctValues($column));
        }

        /** @var non-empty-string $column */
        return Cache::remember(
            key: $this->cacheKey($column),
            ttl: now()->addSeconds($this->cacheTtlSeconds()),
            callback: fn (): array => $this->normalizeValues($this->query->distinctValues($column)),
        );
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

    private function isCacheEnabled(): bool
    {
        return MoonTrailConfig::filterCacheEnabled();
    }

    private function cacheTtlSeconds(): int
    {
        return MoonTrailConfig::filterCacheTtl();
    }

    private function cacheKey(string $column): string
    {
        $driver = MoonTrailConfig::activityDriver();

        return sprintf(
            'moontrail.filter_options.%s.%s.%s',
            $driver,
            md5($this->query->modelClass()),
            $column,
        );
    }
}
