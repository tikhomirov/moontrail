<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Installer\ConfigUpdater;

it('updates tracked and auto track model arrays without duplicates', function (): void {
    $path = sys_get_temp_dir() . '/moontrail-config-updater-' . uniqid('', true) . '.php';

    file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

return [
    'auto_track_models' => [
        // old
    ],
    'tracked_models' => [
        // old
    ],
];
PHP
    );

    $updater = new ConfigUpdater;

    $result = $updater->updateTrackedModels($path, [
        'App\\Models\\User',
        'App\\Models\\User',
        \MoonShine\Laravel\Models\MoonshineUser::class,
    ]);

    expect($result)->toBeTrue();

    /** @var array{auto_track_models: array<int, string>, tracked_models: array<int, string>} $config */
    $config = include $path;

    expect($config['auto_track_models'])->toBe([
        'App\\Models\\User',
        \MoonShine\Laravel\Models\MoonshineUser::class,
    ])->and($config['tracked_models'])->toBe([
        'App\\Models\\User',
        \MoonShine\Laravel\Models\MoonshineUser::class,
    ]);
});
