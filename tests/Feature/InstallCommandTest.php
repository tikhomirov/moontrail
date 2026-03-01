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
    config()->set('moontrail.installer.non_interactive.publish_config', true);
    config()->set('moontrail.installer.non_interactive.publish_views', true);
    config()->set('moontrail.installer.non_interactive.publish_lang', true);
    config()->set('moontrail.installer.non_interactive.run_migrations', true);

    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->expectsOutputToContain('Published config')
        ->expectsOutputToContain('Published views')
        ->expectsOutputToContain('Published lang')
        ->expectsOutputToContain('Migrations completed')
        ->expectsOutputToContain('Publish steps: config, views, lang')
        ->expectsOutputToContain('Migrations: executed')
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

    config()->set('moontrail.installer.default_models', ['App\\Models\\User']);
    config()->set('moontrail.installer.safe_mode_default', true);

    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->expectsOutputToContain('Manual steps for App\\MoonShine\\Resources\\UserResource:')
        ->expectsOutputToContain('Mode: SAFE')
        ->assertExitCode(0);
});

it('clears cache after publishing assets', function (): void {
    config()->set('moontrail.installer.non_interactive.publish_assets', true);

    $this->artisan('moontrail:install', ['--no-interaction' => true])
        ->expectsOutputToContain('Published assets')
        ->expectsOutputToContain('Cache cleared to ensure fresh assets')
        ->assertExitCode(0);
});
