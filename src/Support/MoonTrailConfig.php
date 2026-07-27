<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MoonTrail\Resources\MoonTrailResource;

use function config;
use function is_array;
use function is_numeric;
use function is_string;
use function max;

/**
 * Typed accessor for the moontrail configuration file.
 *
 * This class only wraps config() calls to provide defaults, type narrowing
 * and a single place to look up documented keys. It does not invent new
 * configuration values or restructure the published config.
 */
final class MoonTrailConfig
{
    // -------------------------------------------------------------------------
    // Activity
    // -------------------------------------------------------------------------

    public static function activityDriver(): string
    {
        $driver = config('moontrail.activity_logger', 'auto');

        return is_string($driver) && $driver !== '' ? $driver : 'auto';
    }

    // -------------------------------------------------------------------------
    // Tracking > Versions
    // -------------------------------------------------------------------------

    public static function versioningEnabled(): bool
    {
        return (bool) config('moontrail.versioning.enabled', true);
    }

    public static function versionLimit(): int
    {
        $raw = config('moontrail.versioning.max_versions');

        return is_numeric($raw) ? (int) $raw : 50;
    }

    public static function versionOnLimit(): string
    {
        $raw = config('moontrail.versioning.overflow_strategy');

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
        $models = config('moontrail.auto_track_models', []);

        $values = is_array($models) ? array_values($models) : [];

        /** @var list<class-string> $values */
        return $values;
    }

    public static function autoTrackWriteActivity(): bool
    {
        return (bool) config('moontrail.auto_track.log_to_activity', true);
    }

    public static function autoTrackOnError(): string
    {
        return (bool) config('moontrail.silent_failures', false) ? 'ignore' : 'report';
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
        $raw = config('moontrail.ui.hidden_fields', []);

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
        $raw = config('moontrail.ui.masked_fields', []);

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
        if (! (bool) config('moontrail.rollback.validate', true)) {
            return 'none';
        }

        if ((bool) config('moontrail.rollback.require_rules', false)) {
            return 'required';
        }

        return 'if_rules_provided';
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public static function filterSource(): string
    {
        $raw = config('moontrail.filter_options.strategy', 'database_distinct');

        return is_string($raw) && $raw !== '' ? $raw : 'database_distinct';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function filterStatic(): array
    {
        /** @var array<string, list<string>>|mixed $raw */
        $raw = config('moontrail.filter_options.static', []);

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
        $raw = config("moontrail.filter_options.static.{$key}", []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<string> $values */
        return $values;
    }

    public static function filterCacheEnabled(): bool
    {
        return (bool) config('moontrail.filter_options.cache.enabled', false);
    }

    public static function filterCacheTtl(): int
    {
        $raw = config('moontrail.filter_options.cache.ttl', 60);

        return is_numeric($raw) ? max(1, (int) $raw) : 60;
    }

    public static function filterWarnOnExpensiveQueries(): bool
    {
        $raw = config('moontrail.filter_options.warn_on_expensive_distinct_values');

        if ($raw !== null) {
            return (bool) $raw;
        }

        return (bool) config('moontrail.ui.warn_on_expensive_distinct_values', true);
    }

    public static function filterWarnThreshold(): int
    {
        $raw = config('moontrail.filter_options.distinct_values_warn_threshold');

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        $fallback = config('moontrail.ui.distinct_values_warn_threshold', 50000);

        return is_numeric($fallback) ? (int) $fallback : 50000;
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
        return (bool) config('moontrail.menu.show_all_item', true);
    }

    public static function menuGroupModels(): bool
    {
        return (bool) config('moontrail.menu.show_children', true);
    }

    /**
     * @return list<class-string>
     */
    public static function menuModels(): array
    {
        /** @var array<int, class-string>|mixed $raw */
        $raw = config('moontrail.tracked_models', []);

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
        $raw = config('moontrail.menu.exclude_models', []);

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
        return (bool) config('moontrail.resource.in_menu', true);
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
        $raw = config('moontrail.pruning.retention_days', 90);

        return is_numeric($raw) ? max(1, (int) $raw) : 90;
    }

    // -------------------------------------------------------------------------
    // Installer
    // -------------------------------------------------------------------------

    public static function installerDefaultSafeMode(): bool
    {
        return (bool) config('moontrail.installer.safe_mode_default', true);
    }

    /**
     * @return list<string>
     */
    public static function installerSuggestedModels(): array
    {
        /** @var array<int, string>|mixed $raw */
        $raw = config('moontrail.installer.default_models', []);

        $values = is_array($raw) ? array_values($raw) : [];

        /** @var list<string> $values */
        return $values;
    }
}
