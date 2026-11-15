<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Support\StylesheetAsset;

it('appends the published file mtime as a cache-busting version', function (): void {
    $path = public_path('vendor/moontrail/moontrail.css');
    $directory = dirname($path);
    $createdDirectory = ! is_dir($directory);

    if ($createdDirectory) {
        mkdir($directory, 0777, true);
    }

    $existed = is_file($path);
    $previous = $existed ? (string) file_get_contents($path) : null;

    file_put_contents($path, '/* test */');
    touch($path, 1_700_000_000);
    clearstatcache(true, $path);

    $url = StylesheetAsset::url();

    expect($url)->toContain('vendor/moontrail/moontrail.css')
        ->and($url)->toContain('?v=1700000000');

    if ($existed) {
        file_put_contents($path, (string) $previous);
    } else {
        unlink($path);
    }

    if ($createdDirectory) {
        rmdir($directory);
    }
});

it('falls back to the plain asset url when the published file is missing', function (): void {
    $path = public_path('vendor/moontrail/moontrail.css');
    $existed = is_file($path);
    $previous = $existed ? (string) file_get_contents($path) : null;

    if ($existed) {
        unlink($path);
    }

    clearstatcache(true, $path);

    expect(StylesheetAsset::url())->toContain('vendor/moontrail/moontrail.css')
        ->not->toContain('?v=');

    if ($previous !== null) {
        file_put_contents($path, $previous);
    }
});
