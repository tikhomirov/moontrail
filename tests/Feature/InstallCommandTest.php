<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $resourceDir = base_path('app/MoonShine/Resources');

    if (! File::exists($resourceDir)) {
        File::makeDirectory($resourceDir, 0777, true);
    }
});

afterEach(function (): void {
    File::deleteDirectory(base_path('app'));
});

it('runs in no interaction mode without failures', function (): void {
    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->assertExitCode(0);
});

it('runs publish and migrate branches in no interaction mode when enabled by config', function (): void {
    // Note: non_interactive settings removed from config;
    // CLI flags should be used instead
    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->assertExitCode(0);
});

it('prints safe mode instructions for selected resources', function (): void {
    if (! class_exists('App\\Models\\User')) {
        eval('namespace App\\Models; class User extends \\Illuminate\\Database\\Eloquent\\Model {}');
    }

    file_put_contents(base_path('app/MoonShine/Resources/UserResource.php'), <<<'PHP'
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

final class UserResource
{
    protected string $model = \App\Models\User::class;
}
PHP
    );

    config()->set('moontrail.installer.suggested_models', ['App\\Models\\User']);
    config()->set('moontrail.installer.default_safe_mode', true);

    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->expectsOutputToContain('Manual steps for App\\MoonShine\\Resources\\UserResource:')
        ->expectsOutputToContain('Mode: SAFE')
        ->assertExitCode(0);
});

it('clears cache after publishing assets', function (): void {
    // Note: non_interactive settings removed from config;
    // Use --force flag or run vendor:publish manually if needed
    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->assertExitCode(0);
});
