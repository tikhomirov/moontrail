<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Resources\MoonTrailResource;

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Backend
    |--------------------------------------------------------------------------
    |
    | Supported drivers:
    | - auto:    use Spatie when installed, otherwise use native database logger
    | - spatie:  force spatie/laravel-activitylog
    | - database: use MoonTrail's native activity table
    | - none:    disable activity log writes, keep versioning features
    | - custom:  resolve ActivityLoggerContract + ActivityQueryContract from the container
    |
    */
    'activity' => [
        'driver' => env('MOONTRAIL_DRIVER', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking
    |--------------------------------------------------------------------------
    */
    'tracking' => [
        'versions' => [
            'enabled' => true,

            // 0 = unlimited
            'limit' => 50,

            // delete_oldest | prevent
            'on_limit' => 'delete_oldest',
        ],

        'auto' => [
            /*
            | Models that should be observed without adding HasMoonTrail / HasMoonTrailVersioning.
            | Intended for third-party or external models.
            */
            'models' => [
                // \MoonShine\Laravel\Models\MoonshineUser::class,
            ],

            /*
            | When true, auto-tracked models also write activity entries.
            | When false, only model_versions are written.
            */
            'write_activity' => true,

            /*
            | Error policy for observer failures during auto-tracking:
            | - report: call report($exception)
            | - ignore: swallow observer failures
            */
            'on_error' => 'report',
        ],

        'sensitive' => [
            /*
            | Hidden fields are removed from activity payloads and excluded from diffs.
            */
            'hide' => [
                'password',
                'remember_token',
                'two_factor_secret',
            ],

            /*
            | Masked fields remain present in diff output but are redacted.
            */
            'mask' => [
                'password',
                'remember_token',
                'two_factor_secret',
                'api_key',
                'secret',
                'token',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    |
    | Validation modes:
    | - none:               never validate snapshot data on rollback
    | - if_rules_provided:  validate only when caller/resource provides rules
    | - required:           fail when rollback rules are unavailable
    |
    */
    'rollback' => [
        'validation' => 'if_rules_provided',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        // database_distinct | static
        'source' => env('MOONTRAIL_FILTER_SOURCE', 'database_distinct'),

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

        'performance' => [
            'warn_on_expensive_queries' => true,
            'warn_threshold'            => 50000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'per_page'                 => 20,
        'date_format'              => 'd.m.Y H:i:s',
        'warn_if_tailwind_missing' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu
    |--------------------------------------------------------------------------
    */
    'menu' => [
        'enabled' => true,
        'label'   => null,

        /*
        | Show the top-level "All activity" entry.
        */
        'show_all' => true,

        /*
        | When true, render model-specific child items.
        | When false, collapse to a single top-level menu item.
        */
        'group_models' => true,

        /*
        | Extra models that should appear as model-filtered menu items.
        | These models are not auto-observed by themselves.
        */
        'models' => [
            // \App\Models\Post::class,
        ],

        /*
        | Models excluded from model-specific menu entries.
        */
        'exclude' => [
            // \App\Models\User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource
    |--------------------------------------------------------------------------
    */
    'resource' => [
        'class' => MoonTrailResource::class,

        /*
        | Register the resource in MoonShine core.
        */
        'register' => true,

        'menu_icon' => 'clock',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    */
    'pruning' => [
        'enabled' => true,
        'days'    => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Installer
    |--------------------------------------------------------------------------
    */
    'installer' => [
        'default_safe_mode' => true,

        /*
        | Suggested models preselected by the installer.
        | This is a UX hint for the CLI wizard, not a runtime behavior switch.
        */
        'suggested_models' => [
            'App\\Models\\User',
            'MoonShine\\Laravel\\Models\\MoonshineUser',
            'App\\Models\\Role',
            'MoonShine\\Laravel\\Models\\Role',
        ],
    ],
];
