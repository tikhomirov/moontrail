<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Diff\HtmlDiffRenderer;
use MoonShine\MoonTrail\Enums\ChangeType;

beforeEach(function (): void {
    config()->set('moontrail.ui.hidden_fields', []);
});

it('renders a table with field, before, after columns', function (): void {
    $renderer = new HtmlDiffRenderer;
    $changes = [
        'name' => new FieldChange('name', 'Old', 'New', ChangeType::Modified),
    ];

    $html = $renderer->render($changes);

    expect($html)
        ->toContain('<table')
        ->toContain('name')
        ->toContain('Old')
        ->toContain('New');
});

it('renders em-dash for null values', function (): void {
    $renderer = new HtmlDiffRenderer;
    $changes = [
        'bio' => new FieldChange('bio', null, 'Some bio', ChangeType::Modified),
    ];

    $html = $renderer->render($changes);

    expect($html)->toContain('—');
});

it('renders boolean values as true/false strings', function (): void {
    $renderer = new HtmlDiffRenderer;
    $changes = [
        'active' => new FieldChange('active', false, true, ChangeType::Modified),
    ];

    $html = $renderer->render($changes);

    expect($html)
        ->toContain('false')
        ->toContain('true');
});

it('renders array values as JSON (html-encoded)', function (): void {
    $renderer = new HtmlDiffRenderer;
    $changes = [
        'meta' => new FieldChange('meta', ['a' => 1], ['b' => 2], ChangeType::Modified),
    ];

    $html = $renderer->render($changes);

    expect($html)
        ->toContain('&quot;a&quot;')
        ->toContain('&quot;b&quot;');
});

it('renders empty table body when no changes given', function (): void {
    $renderer = new HtmlDiffRenderer;

    $html = $renderer->render([]);

    expect($html)
        ->not->toContain('<table')
        ->toContain((string) __('moontrail::ui.no_changes'));
});

it('applies correct css class per change type', function (): void {
    $renderer = new HtmlDiffRenderer;
    $changes = DiffComputer::compute(
        before: ['name' => 'Old'],
        after: ['name' => 'New'],
    );

    $html = $renderer->render($changes);

    expect($html)->toContain('border-l-blue-500');
});

it('escapes html special characters in values', function (): void {
    $renderer = new HtmlDiffRenderer;
    $changes = [
        'title' => new FieldChange('title', '<script>alert(1)</script>', 'safe', ChangeType::Modified),
    ];

    $html = $renderer->render($changes);

    expect($html)
        ->not->toContain('<script>alert(1)</script>')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});
