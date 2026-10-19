<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('registers moonshine views in a dedicated namespace', function (): void {
    $hints = view()->getFinder()->getHints();

    expect($hints)->toHaveKey('moontrail-moonshine')
        ->and($hints['moontrail-moonshine'])->not->toBeEmpty();
});

it('keeps the shared view namespace free of moonshine dependencies', function (): void {
    $paths = view()->getFinder()->getHints()['moontrail'] ?? [];

    expect($paths)->not->toBeEmpty();

    foreach ($paths as $path) {
        foreach (File::allFiles($path) as $view) {
            expect(File::get($view->getPathname()))
                ->not->toContain('x-moonshine::')
                ->not->toContain('moonshine()')
                ->not->toContain('MoonShine\\Laravel');
        }
    }
});

it('keeps moonshine views out of the shared view directory', function (): void {
    $hints = view()->getFinder()->getHints();

    foreach ($hints['moontrail-moonshine'] as $path) {
        expect($path)->toEndWith('resources/moonshine-views');
    }
});
