<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Installer\ConfigUpdater;

it('updates tracking.auto.models and menu.models arrays without duplicates', function (): void {
    $path = sys_get_temp_dir() . '/moontrail-config-updater-' . uniqid('', true) . '.php';

    file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

return [
    'tracking' => [
        'auto' => [
            'models' => [
                // old
            ],
        ],
    ],
    'menu' => [
        'models' => [
            // old
        ],
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

    /** @var array{tracking: array{auto: array{models: array<int, string>}}, menu: array{models: array<int, string>}} $config */
    $config = include $path;

    expect($config['tracking']['auto']['models'])->toBe([
        'App\\Models\\User',
        \MoonShine\Laravel\Models\MoonshineUser::class,
    ])->and($config['menu']['models'])->toBe([
        'App\\Models\\User',
        \MoonShine\Laravel\Models\MoonshineUser::class,
    ]);
});
