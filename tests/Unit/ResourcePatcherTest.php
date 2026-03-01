<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Installer\ResourcePatcher;

it('patches resource by adding trait import and usage', function (): void {
    $path = sys_get_temp_dir() . '/moontrail-resource-patcher-' . uniqid('', true) . '.php';

    file_put_contents($path, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use MoonShine\Laravel\Resources\ModelResource;

final class UserResource extends ModelResource
{
}
PHP
    );

    $patcher = new ResourcePatcher;

    expect($patcher->patch($path))->toBeTrue();

    $content = file_get_contents($path);

    expect($content)->toContain('use MoonShine\\MoonTrail\\Traits\\WithMoonTrailTab;')
        ->and($content)->toContain('use WithMoonTrailTab;');
});

it('does not modify resource that already has trait', function (): void {
    $path = sys_get_temp_dir() . '/moontrail-resource-patcher-existing-' . uniqid('', true) . '.php';

    $original = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use MoonShine\MoonTrail\Traits\WithMoonTrailTab;
use MoonShine\Laravel\Resources\ModelResource;

final class UserResource extends ModelResource
{
    use WithMoonTrailTab;
}
PHP;

    file_put_contents($path, $original);

    $patcher = new ResourcePatcher;

    expect($patcher->patch($path))->toBeTrue();

    expect(file_get_contents($path))->toBe($original);
});
