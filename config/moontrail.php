<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Logger
    |--------------------------------------------------------------------------
    |
    | Controls which activity logging backend to use:
    |
    |  'auto'     — Use Spatie if installed, otherwise fall back to native database logger.
    |  'spatie'   — Use spatie/laravel-activitylog (must be installed).
    |  'database' — Use MoonTrail's native moontrail_activity_log table.
    |  'none'     — Disable activity logging entirely (versioning still works).
    |  'custom'   — Resolve ActivityLoggerContract from the container (bind your own).
    |
    */
    'activity_logger' => env('MOONTRAIL_LOGGER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Activity Read Model (custom mode)
    |--------------------------------------------------------------------------
    |
    | Used by UI/read-path components when activity_logger = custom.
    | Must be an existing Eloquent model class (extends Illuminate\Database\Eloquent\Model)
    | compatible with MoonTrail activity fields.
    |
    */
    'activity_model' => \MoonShine\MoonTrail\Models\MoonTrailActivity::class,

    /*
    |--------------------------------------------------------------------------
    | Silent Failures
    |--------------------------------------------------------------------------
    |
    | When false (default), observer exceptions are reported via report().
    | When true, exceptions in the observer are silently swallowed.
    |
    */
    'silent_failures' => false,

    /*
    |--------------------------------------------------------------------------
    | Versioning
    |--------------------------------------------------------------------------
    */
    'versioning' => [
        // Enable automatic version creation on model changes
        'enabled' => true,

        // Maximum number of versions per record (0 = unlimited)
        'max_versions' => 50,

        // Strategy when limit is exceeded: 'delete_oldest' | 'prevent'
        'overflow_strategy' => 'delete_oldest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */
    'rollback' => [
        // Validate data on rollback using provided rules
        'validate' => true,

        // When true, a rollback without any validation rules is denied.
        // When false (default), rollback without rules proceeds without validation.
        'require_rules' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | enabled: Whether scheduled pruning should run.
    | retention_days: Default age threshold used by moontrail:prune when
    |   --days is not provided.
    | schedule: Suggested cadence for scheduler integration.
    |
    */
    'pruning' => [
        'enabled'        => true,
        'retention_days' => 90,
        'schedule'       => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        // Number of records per page in timeline and resource
        'per_page' => 20,

        // Date format used throughout the UI
        'date_format' => 'd.m.Y H:i:s',

        // Log warning when host app does not configure Tailwind for package views
        'warn_if_tailwind_missing' => true,

        // Fields hidden from diff globally
        'hidden_fields' => ['password', 'remember_token', 'two_factor_secret'],

        // Fields shown in diff as masked placeholders
        'masked_fields' => ['password', 'remember_token', 'two_factor_secret', 'api_key', 'secret', 'token'],

        // Log warning when distinct-values filter options are queried on large tables
        'warn_on_expensive_distinct_values' => true,

        // Row threshold for distinct-values performance warning
        'distinct_values_warn_threshold' => 50000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Options
    |--------------------------------------------------------------------------
    |
    | strategy:
    |  - database_distinct: load options via distinct queries
    |  - static: use static arrays below (no DB distinct queries)
    |
    | Deprecated fallback keys for database strategy:
    |  - ui.warn_on_expensive_distinct_values
    |  - ui.distinct_values_warn_threshold
    |
    */
    'filter_options' => [
        'strategy' => env('MOONTRAIL_FILTER_OPTIONS_STRATEGY', 'database_distinct'),

        'static' => [
            'log_names'     => [],
            'events'        => [],
            'subject_types' => [],
            'causer_types'  => [],
        ],

        // When null, falls back to moontrail.ui.warn_on_expensive_distinct_values
        'warn_on_expensive_distinct_values' => null,

        // When null, falls back to moontrail.ui.distinct_values_warn_threshold
        'distinct_values_warn_threshold' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log Resource
    |--------------------------------------------------------------------------
    */
    'resource' => [
        // Resource class (can be replaced with a custom one)
        'class' => \MoonShine\MoonTrail\Resources\MoonTrailResource::class,

        // Register the resource with MoonShine core (required for menu)
        'in_menu' => true,

        // Menu icon (MoonShine built-in icons)
        'menu_icon' => 'clock',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-tracking
    |--------------------------------------------------------------------------
    |
    | Models listed here will be tracked automatically without adding
    | the HasMoonTrail trait. The observer will be attached at boot.
    | Useful for third-party models like MoonshineUser.
    |
    */
    'auto_track_models' => [
        // \MoonShine\Laravel\Models\MoonshineUser::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-tracking activity log options
    |--------------------------------------------------------------------------
    |
    | Controls how the auto-observer behaves when writing activity records.
    |
    | log_to_activity: When true (default), the observer also writes an entry
    |   to the Spatie activity_log table so the diff viewer can display changes.
    |   Set to false to write only model_versions (legacy behaviour).
    |
    */
    'auto_track' => [
        'log_to_activity' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracked Models for Menu
    |--------------------------------------------------------------------------
    |
    | Models listed here will appear as sub-items in the Activity Log menu
    | group (in addition to auto_track_models). Each model gets its own
    | filtered view showing only activity for that model type.
    |
    */
    'tracked_models' => [
        // \App\Models\Post::class,
        // \App\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | enabled:        false — completely hides the menu item (returns null).
    | label:          Override the default menu group/item label.
    | show_all_item:  Whether to show the "All" link (unfiltered view).
    |                 When false and there are no tracked models, the item is hidden entirely.
    | show_children:  false — collapse sub-items, show only a single top-level item.
    | exclude_models: Models to hide from sub-items (even if in tracked_models).
    |
    */
    'menu' => [
        'enabled'        => true,
        'label'          => null,
        'show_all_item'  => true,
        'show_children'  => true,
        'exclude_models' => [
            // \App\Models\User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Installer
    |--------------------------------------------------------------------------
    */
    'installer' => [
        'safe_mode_default' => true,

        'default_models' => [
            'App\\Models\\User',
            'MoonShine\\Laravel\\Models\\MoonshineUser',
            'App\\Models\\Role',
            'MoonShine\\Laravel\\Models\\Role',
        ],

        'non_interactive' => [
            'publish_config' => false,
            'publish_views'  => false,
            'publish_lang'   => false,
            'publish_assets' => false,
            'run_migrations' => false,
        ],
    ],
];
