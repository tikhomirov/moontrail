<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use MoonShine\MoonTrail\Activity\SpatieActivityQuery;
use MoonShine\MoonTrail\Installer\ResourceScanner;
use Spatie\Activitylog\Models\Activity;

it('tests ResourceScanner with various file structures and empty directories', function (): void {
    $scanner = new ResourceScanner;

    expect($scanner->scan('/non/existent/path'))->toBe([]);

    $tempDir = sys_get_temp_dir() . '/moontrail_scanner_test_' . uniqid();
    File::makeDirectory($tempDir, 0777, true);

    // 1. Non-php file
    File::put($tempDir . '/readme.txt', 'Hello world');

    // 2. PHP file without class
    File::put($tempDir . '/helper.php', '<?php echo "test";');

    // 3. PHP file without model
    File::put($tempDir . '/NoModelResource.php', <<<'PHP'
<?php

namespace App\MoonShine\Resources;

class NoModelResource {}
PHP
    );

    // 4. Valid resource
    File::put($tempDir . '/PostResource.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

final class PostResource
{
    protected string $model = \App\Models\Post::class;
}
PHP
    );

    $results = $scanner->scan($tempDir);
    expect($results)->toHaveCount(1)
        ->and($results[0]['name'])->toBe('PostResource')
        ->and($results[0]['class'])->toBe('App\MoonShine\Resources\PostResource')
        ->and($results[0]['model'])->toBe('App\Models\Post');

    File::deleteDirectory($tempDir);
});

it('tests SpatieActivityQuery methods if Spatie is installed', function (): void {
    if (! class_exists(Activity::class)) {
        $this->markTestSkipped('Spatie Activitylog is not installed');
    }

    $query = new SpatieActivityQuery;

    expect($query->modelClass())->toBe(Activity::class);

    $stats = $query->stats();
    expect($stats)->toHaveKeys(['total', 'created', 'updated', 'deleted', 'other']);

    $invalidCol = $query->distinctValues('unknown_column');
    expect($invalidCol)->toBe([]);

    $events = $query->distinctValues('event');
    expect($events)->toBeArray();

    expect($query->find(999999))->toBeNull();
});
