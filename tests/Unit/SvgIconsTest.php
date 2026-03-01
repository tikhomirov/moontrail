<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Support\SvgIcons;

it('created icon returns svg with plus path', function (): void {
    $svg = SvgIcons::created();

    expect($svg)
        ->toBeString()
        ->toContain('<svg')
        ->toContain('M12 4v16m8-8H4');
});

it('updated icon returns svg with pencil path', function (): void {
    $svg = SvgIcons::updated();

    expect($svg)->toBeString()->toContain('<svg');
});

it('deleted icon returns svg with trash path', function (): void {
    $svg = SvgIcons::deleted();

    expect($svg)->toBeString()->toContain('<svg');
});

it('restored icon returns svg string', function (): void {
    $svg = SvgIcons::restored();

    expect($svg)->toBeString()->toContain('<svg');
});

it('rolledBack icon returns svg string', function (): void {
    $svg = SvgIcons::rolledBack();

    expect($svg)->toBeString()->toContain('<svg');
});

it('unknown icon returns svg string', function (): void {
    $svg = SvgIcons::unknown();

    expect($svg)->toBeString()->toContain('<svg');
});

it('applies custom size class to svg', function (): void {
    $svg = SvgIcons::created('w-8 h-8');

    expect($svg)->toContain('w-8 h-8');
});

it('applies extra class to svg', function (): void {
    $svg = SvgIcons::created('w-4 h-4', 'text-red-500');

    expect($svg)->toContain('text-red-500');
});

it('trims class attribute when no extra class given', function (): void {
    $svg = SvgIcons::created('w-4 h-4');

    expect($svg)->not->toContain('w-4 h-4 ');
});

it('diff icon returns svg string', function (): void {
    $svg = SvgIcons::diff();

    expect($svg)->toBeString()->toContain('<svg');
});

it('filter icon returns svg string', function (): void {
    $svg = SvgIcons::filter();

    expect($svg)->toBeString()->toContain('<svg');
});

it('check icon returns svg string', function (): void {
    $svg = SvgIcons::check();

    expect($svg)->toBeString()->toContain('<svg');
});

it('error icon returns svg string', function (): void {
    $svg = SvgIcons::error();

    expect($svg)->toBeString()->toContain('<svg');
});

it('document icon returns svg string', function (): void {
    $svg = SvgIcons::document();

    expect($svg)->toBeString()->toContain('<svg');
});

it('user icon returns svg string', function (): void {
    $svg = SvgIcons::user();

    expect($svg)->toBeString()->toContain('<svg');
});

it('all event icons return valid svg elements with viewBox', function (): void {
    $icons = [
        SvgIcons::created(),
        SvgIcons::updated(),
        SvgIcons::deleted(),
        SvgIcons::restored(),
        SvgIcons::rolledBack(),
        SvgIcons::unknown(),
    ];

    foreach ($icons as $svg) {
        expect($svg)
            ->toContain('<svg')
            ->toContain('</svg>')
            ->toContain('viewBox="0 0 24 24"');
    }
});
