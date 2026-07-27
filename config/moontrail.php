<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Logger
    |--------------------------------------------------------------------------
    |
    | Controls which activity logging backend is used by the package.
    |
    |  'auto'     — Use spatie/laravel-activitylog when installed, otherwise
    |               fall back to MoonTrail's native database logger.
    |  'spatie'   — Force spatie/laravel-activitylog (must be installed).
    |  'database' — Use MoonTrail's native moontrail_activity_log table.
    |  'none'     — Disable activity logging (versioning still works).
    |  'custom'   — Resolve ActivityLoggerContract and ActivityQueryContract
    |               from the container (bind your own implementations).
    |
    */
    'activity_logger' => env('MOONTRAIL_LOGGER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Activity Model (custom mode)
    |--------------------------------------------------------------------------
    |
    | Eloquent model used by the read-path when activity_logger is 'custom'.
    | Must extend Illuminate\Database\Eloquent\Model and be compatible with
    | MoonTrail activity fields.
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
        // Enable automatic version creation on model changes.
        'enabled' => true,

        // Maximum number of versions per record (0 = unlimited).
        'max_versions' => 50,

        // Strategy when the limit is exceeded: 'delete_oldest' | 'prevent'.
        'overflow_strategy' => 'delete_oldest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */
    'rollback' => [
        // Validate snapshot data on rollback when validation rules are provided.
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
    |  enabled:        Whether scheduled pruning should run.
    |  retention_days: Default age threshold used by moontrail:prune when
    |                   --days is not provided.
    |  schedule:       Suggested cadence for scheduler integration.
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
        // Number of records per page in timeline and resource.
        'per_page' => 20,

        // Date format used throughout the UI.
        'date_format' => 'd.m.Y H:i:s',

        // Log a warning when the host app does not configure Tailwind for package views.
        'warn_if_tailwind_missing' => true,

        // Fields hidden from diff output globally.
        'hidden_fields' => ['password', 'remember_token', 'two_factor_secret'],

        // Fields shown in diff output as masked placeholders.
        'masked_fields' => ['password', 'remember_token', 'two_factor_secret', 'api_key', 'secret', 'token'],

        // Log a warning when distinct-values filter options are queried on large tables.
        'warn_on_expensive_distinct_values' => true,

        // Row threshold for the distinct-values performance warning.
        'distinct_values_warn_threshold' => 50000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Options
    |--------------------------------------------------------------------------
    |
    |  strategy:
    |   - 'database_distinct' — load options via DISTINCT database queries.
    |   - 'static'            — use the static arrays below (no DB queries).
    |
    |  static: Predefined values for each filter. Used only when strategy = 'static'.
    |
    |  cache: Cache distinct-values queries to reduce load.
    |         enabled — turn caching on/off.
    |         ttl     — cache lifetime in seconds.
    |
    |  Performance warnings can also be controlled via ui.* keys above for
    |  backward compatibility.
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

        'cache' => [
            'enabled' => env('MOONTRAIL_FILTER_CACHE', false),
            'ttl'     => env('MOONTRAIL_FILTER_CACHE_TTL', 60),
        ],

        'warn_on_expensive_distinct_values' => null,
        'distinct_values_warn_threshold'    => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log Resource
    |--------------------------------------------------------------------------
    */
    'resource' => [
        // Resource class (can be replaced with a custom one).
        'class' => \MoonShine\MoonTrail\Resources\MoonTrailResource::class,

        // Register the resource with MoonShine core (required for menu).
        'in_menu' => true,

        // Menu icon (MoonShine built-in icons).
        'menu_icon' => 'clock',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-tracking
    |--------------------------------------------------------------------------
    |
    | Models listed here will be tracked automatically without adding the
    | HasMoonTrail trait. The observer will be attached at boot.
    | Useful for third-party models like MoonshineUser.
    |
    */
    'auto_track_models' => [
        // \MoonShine\Laravel\Models\MoonshineUser::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-tracking Activity Log Options
    |--------------------------------------------------------------------------
    |
    |  log_to_activity: When true (default), the observer also writes an entry
    |   to the activity table so the diff viewer can display changes.
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
    |  enabled:        false — completely hides the menu item (returns null).
    |  label:          Override the default menu group/item label.
    |  show_all_item:  Whether to show the "All" link (unfiltered view).
    |                  When false and there are no tracked models, the item is
    |                  hidden entirely.
    |  show_children:  false — collapse sub-items, show only a single top-level item.
    |  exclude_models:   Models to hide from sub-items (even if in tracked_models).
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
        // Default value for the --safe option in interactive mode.
        'safe_mode_default' => true,

        // Models preselected by the installer wizard.
        'default_models' => [
            'App\\Models\\User',
            'MoonShine\\Laravel\\Models\\MoonshineUser',
            'App\\Models\\Role',
            'MoonShine\\Laravel\\Models\\Role',
        ],

        // Non-interactive mode defaults (for CI/automated runs).
        'non_interactive' => [
            'publish_config' => false,
            'publish_views'  => false,
            'publish_lang'   => false,
            'publish_assets' => false,
            'run_migrations' => false,
            'auto_patch'     => false,
        ],
    ],
];
