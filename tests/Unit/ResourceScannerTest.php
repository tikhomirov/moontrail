<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Installer\ResourceScanner;

it('detects moonshine resources and linked models', function (): void {
    $dir = sys_get_temp_dir() . '/moontrail-resource-scanner-' . uniqid('', true);
    mkdir($dir, 0777, true);

    file_put_contents($dir . '/UserResource.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

final class UserResource
{
    protected string $model = \App\Models\User::class;
}
PHP
    );

    $scanner = new ResourceScanner;
    $resources = $scanner->scan($dir);

    expect($resources)->toHaveCount(1)
        ->and($resources[0]['name'])->toBe('UserResource')
        ->and($resources[0]['class'])->toBe('App\\MoonShine\\Resources\\UserResource')
        ->and($resources[0]['model'])->toBe('App\\Models\\User');
});
