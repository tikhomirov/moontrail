<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Components\DiffViewer;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Diff\FieldChange;
use MoonShine\MoonTrail\Enums\ChangeType;

it('renders diff viewer with changes', function (): void {
    $changes = [
        'name'  => new FieldChange('name', 'Old', 'New', ChangeType::Modified),
        'email' => new FieldChange('email', null, 'test@mail.com', ChangeType::Added),
    ];

    $component = DiffViewer::make($changes);
    $html = (string) $component->render();

    expect($html)
        ->toContain('name')
        ->toContain('Old')
        ->toContain('New')
        ->toContain('email')
        ->toContain('test@mail.com');
});

it('renders empty state when no changes', function (): void {
    $component = DiffViewer::make([]);
    $html = (string) $component->render();

    expect($html)->toContain(__('moontrail::ui.no_changes'));
});

it('renders compact mode without status column', function (): void {
    $changes = [
        'name' => new FieldChange('name', 'A', 'B', ChangeType::Modified),
    ];

    $component = DiffViewer::make($changes, compactMode: true);
    $html = (string) $component->render();

    expect($html)
        ->toContain('name')
        ->not->toContain(__('moontrail::ui.status'));
});

it('does not render hidden fields', function (): void {
    $changes = DiffComputer::compute(
        ['password' => 'old-secret', 'email' => 'old@mail.com'],
        ['password' => 'new-secret', 'email' => 'new@mail.com'],
        hiddenFields: ['password'],
    );

    $component = DiffViewer::make($changes);
    $html = (string) $component->render();

    expect($html)
        ->not->toContain('password')
        ->toContain('email');
});

it('renders masked fields without leaking original value or json controls', function (): void {
    $changes = [
        'token' => new FieldChange('token', '********', '********', ChangeType::Modified, true),
    ];

    $component = DiffViewer::make($changes);
    $html = (string) $component->render();

    expect($html)
        ->toContain('token')
        ->toContain('********')
        ->not->toContain('old-secret')
        ->not->toContain('new-secret')
        ->not->toContain('title="')
        ->not->toContain((string) __('moontrail::ui.expand'))
        ->not->toContain((string) __('moontrail::ui.copy'));
});
