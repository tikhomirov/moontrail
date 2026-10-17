<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->app->useConfigPath(sys_get_temp_dir() . '/moontrail-install-' . uniqid());
    File::ensureDirectoryExists(config_path());
});

afterEach(function (): void {
    File::deleteDirectory(config_path());
});

it('publishes configuration and only version migrations for spatie', function (): void {
    $this->artisan('moontrail:install', ['--no-interaction' => true, '--ui' => 'headless', '--logger' => 'spatie'])
        ->assertExitCode(0);

    expect(File::exists(config_path('moontrail.php')))->toBeTrue()
        ->and(File::exists(database_path('migrations/2024_01_01_000001_create_model_versions_table.php')))->toBeTrue()
        ->and(File::exists(database_path('migrations/2024_01_01_000002_create_moontrail_activity_log_table.php')))->toBeFalse()
        ->and(File::get(config_path('moontrail.php')))->toContain("'ui_adapter' => 'headless'");
});

it('does not overwrite existing configuration when run again', function (): void {
    $this->artisan('moontrail:install', ['--no-interaction' => true, '--ui' => 'headless', '--logger' => 'none'])
        ->assertExitCode(0);

    File::put(config_path('moontrail.php'), '<?php return ["custom" => true];');

    $this->artisan('moontrail:install', ['--no-interaction' => true, '--ui' => 'headless', '--logger' => 'spatie'])
        ->assertExitCode(0);

    expect(File::get(config_path('moontrail.php')))->toBe('<?php return ["custom" => true];');
});

it('rejects unsupported adapters before publishing files', function (): void {
    $before = File::exists(config_path('moontrail.php')) ? File::get(config_path('moontrail.php')) : null;

    $this->artisan('moontrail:install', ['--no-interaction' => true, '--ui' => 'unknown'])
        ->assertExitCode(1);

    expect(File::exists(config_path('moontrail.php')) ? File::get(config_path('moontrail.php')) : null)->toBe($before);
});
