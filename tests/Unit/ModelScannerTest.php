<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Installer\ModelScanner;

it('finds model classes in a models directory', function (): void {
    $dir = sys_get_temp_dir() . '/moontrail-model-scanner-' . uniqid('', true);
    mkdir($dir, 0777, true);

    file_put_contents($dir . '/Post.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Models;

class Post
{
}
PHP
    );

    file_put_contents($dir . '/README.txt', 'skip');

    $scanner = new ModelScanner;
    $models = $scanner->scan($dir);

    expect($models)->toBe(['App\\Models\\Post']);
});
