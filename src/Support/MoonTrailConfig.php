<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MoonTrail\Resources\MoonTrailResource;

use function config;
use function is_array;
use function is_numeric;
use function is_string;
use function max;

final class MoonTrailConfig
{
    // -------------------------------------------------------------------------
    // Activity
    // -------------------------------------------------------------------------

    public static function activityDriver(): string
    {
        $driver = config('moontrail.activity.driver', 'auto');

        return is_string($driver) && $driver !== '' ? $driver : 'auto';
    }

    // -------------------------------------------------------------------------
    // Tracking > Versions
    // -------------------------------------------------------------------------

    public static function versioningEnabled(): bool
    {
        return (bool) config('moontrail.tracking.versions.enabled', true);
    }

    public static function versionLimit(): int
    {
        $raw = config('moontrail.tracking.versions.limit');

        return is_numeric($raw) ? (int) $raw : 50;
    }

    public static function versionOnLimit(): string
    {
        $raw = config('moontrail.tracking.versions.on_limit');

        return is_string($raw) && $raw !== '' ? $raw : 'delete_oldest';
    }

    // -------------------------------------------------------------------------
    // Tracking > Auto
    // -------------------------------------------------------------------------

    /**
     * @return list<class-string>
     */
    public static function autoTrackModels(): array
    {
        /** @var array<int, class-string>|mixed $models */
        $models = config('moontrail.tracking.auto.models', []);

        $values = is_array($models) ? array_values($models) : [];

        /** @var list<class-string> $values */
        return $values;
    }

    public static function autoTrackWriteActivity(): bool
    {
        return (bool) config('moontrail.tracking.auto.write_activity', true);
    }

    public static function autoTrackOnError(): string
    {
        $raw = config('moontrail.tracking.auto.on_error');

        return is_string($raw) && $raw !== '' ? $raw : 'report';
    }

    // -------------------------------------------------------------------------
    // Tracking > Sensitive
    // -------------------------------------------------------------------------

    /**
     * @return list<string>
     */
    public static function sensitiveHide(): array
    {
        /** @var array<int, string>|mixed $raw */
        $raw = config('moontrail.tracking.sensitive.hide', []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<string> $values */
        return $values;
    }

    /**
     * @return list<string>
     */
    public static function sensitiveMask(): array
    {
        /** @var array<int, string>|mixed $raw */
        $raw = config('moontrail.tracking.sensitive.mask', []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<string> $values */
        return $values;
    }

    // -------------------------------------------------------------------------
    // Rollback
    // -------------------------------------------------------------------------

    /**
     * @return 'none'|'if_rules_provided'|'required'
     */
    public static function rollbackValidation(): string
    {
        $raw = config('moontrail.rollback.validation', 'if_rules_provided');

        if (is_string($raw) && in_array($raw, ['none', 'if_rules_provided', 'required'], true)) {
            return $raw;
        }

        return 'if_rules_provided';
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public static function filterSource(): string
    {
        $raw = config('moontrail.filters.source', 'database_distinct');

        return is_string($raw) && $raw !== '' ? $raw : 'database_distinct';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function filterStatic(): array
    {
        /** @var array<string, list<string>>|mixed $raw */
        $raw = config('moontrail.filters.static', []);

        /** @var array<string, list<string>> $value */
        $value = is_array($raw) ? $raw : [];

        return $value;
    }

    /**
     * @return list<string>
     */
    public static function filterStaticValues(string $key): array
    {
        /** @var array<int, string>|mixed $raw */
        $raw = config("moontrail.filters.static.{$key}", []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<string> $values */
        return $values;
    }

    public static function filterCacheEnabled(): bool
    {
        return (bool) config('moontrail.filters.cache.enabled', false);
    }

    public static function filterCacheTtl(): int
    {
        $raw = config('moontrail.filters.cache.ttl', 60);

        return is_numeric($raw) ? max(1, (int) $raw) : 60;
    }

    public static function filterWarnOnExpensiveQueries(): bool
    {
        return (bool) config('moontrail.filters.performance.warn_on_expensive_queries', true);
    }

    public static function filterWarnThreshold(): int
    {
        $raw = config('moontrail.filters.performance.warn_threshold', 50000);

        return is_numeric($raw) ? (int) $raw : 50000;
    }

    // -------------------------------------------------------------------------
    // UI
    // -------------------------------------------------------------------------

    public static function uiPerPage(): int
    {
        $raw = config('moontrail.ui.per_page');

        return is_numeric($raw) ? max(1, (int) $raw) : 20;
    }

    public static function uiDateFormat(): string
    {
        $raw = config('moontrail.ui.date_format');

        return is_string($raw) && $raw !== '' ? $raw : 'd.m.Y H:i:s';
    }

    public static function uiWarnIfTailwindMissing(): bool
    {
        return (bool) config('moontrail.ui.warn_if_tailwind_missing', true);
    }

    // -------------------------------------------------------------------------
    // Menu
    // -------------------------------------------------------------------------

    public static function menuEnabled(): bool
    {
        return (bool) config('moontrail.menu.enabled', true);
    }

    public static function menuLabel(): ?string
    {
        $raw = config('moontrail.menu.label');

        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    public static function menuShowAll(): bool
    {
        return (bool) config('moontrail.menu.show_all', true);
    }

    public static function menuGroupModels(): bool
    {
        return (bool) config('moontrail.menu.group_models', true);
    }

    /**
     * @return list<class-string>
     */
    public static function menuModels(): array
    {
        /** @var array<int, class-string>|mixed $raw */
        $raw = config('moontrail.menu.models', []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<class-string> $values */
        return $values;
    }

    /**
     * @return list<class-string>
     */
    public static function menuExclude(): array
    {
        /** @var array<int, class-string>|mixed $raw */
        $raw = config('moontrail.menu.exclude', []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<class-string> $values */
        return $values;
    }

    // -------------------------------------------------------------------------
    // Resource
    // -------------------------------------------------------------------------

    /**
     * @return class-string
     */
    public static function resourceClass(): string
    {
        /** @var class-string|mixed $raw */
        $raw = config('moontrail.resource.class');

        $value = is_string($raw) && $raw !== '' ? $raw : MoonTrailResource::class;

        /** @var class-string $value */
        return $value;
    }

    public static function resourceRegister(): bool
    {
        return (bool) config('moontrail.resource.register', true);
    }

    public static function resourceMenuIcon(): string
    {
        $raw = config('moontrail.resource.menu_icon');

        return is_string($raw) && $raw !== '' ? $raw : 'clock';
    }

    // -------------------------------------------------------------------------
    // Pruning
    // -------------------------------------------------------------------------

    public static function pruningEnabled(): bool
    {
        return (bool) config('moontrail.pruning.enabled', true);
    }

    public static function pruningDays(): int
    {
        $raw = config('moontrail.pruning.days', 90);

        return is_numeric($raw) ? max(1, (int) $raw) : 90;
    }

    // -------------------------------------------------------------------------
    // Installer
    // -------------------------------------------------------------------------

    public static function installerDefaultSafeMode(): bool
    {
        return (bool) config('moontrail.installer.default_safe_mode', true);
    }

    /**
     * @return list<string>
     */
    public static function installerSuggestedModels(): array
    {
        /** @var array<int, string>|mixed $raw */
        $raw = config('moontrail.installer.suggested_models', []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<string> $values */
        return $values;
    }
}
