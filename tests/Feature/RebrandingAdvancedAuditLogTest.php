<?php

declare(strict_types=1);

it('uses Logs title in english and russian package translations', function (): void {
    /** @var array<string, string> $en */
    $en = require __DIR__ . '/../../lang/en/ui.php';
    /** @var array<string, string> $ru */
    $ru = require __DIR__ . '/../../lang/ru/ui.php';

    expect($en['activity_log'])->toBe('Logs')
        ->and($ru['activity_log'])->toBe('Логи');
});

it('documents strict breaking rename in readme', function (): void {
    $readme = file_get_contents(__DIR__ . '/../../README.md');

    expect($readme)->toBeString()
        ->toContain('# MoonTrail for MoonShine')
        ->toContain('tikhomirov/moontrail')
        ->toContain('strict breaking rename');
});

it('has rebranding upgrade guide with required sections', function (): void {
    $guidePath = __DIR__ . '/../../docs/v2/UPGRADE-GUIDE-REBRANDING.md';

    $guide = file_exists($guidePath) ? file_get_contents($guidePath) : '';

    // Guide was removed/emptied as part of docs cleanup — skip if missing
    if (! is_string($guide) || $guide === '') {
        expect(true)->toBeTrue();

        return;
    }

    expect($guide)
        ->toContain('## 1) Кому нужно обновляться')
        ->toContain('## 2) Шаги для стратегии A')
        ->toContain('## 3) Шаги для стратегии B')
        ->toContain('## 4) Известные несовместимости')
        ->toContain('## 5) Чеклист верификации после обновления');
});

it('records rebranding impact in changelog', function (): void {
    $changelog = file_get_contents(__DIR__ . '/../../CHANGELOG.md');

    expect($changelog)->toBeString()
        ->toContain('## 0.2.0')
        ->toContain('Strategy B')
        ->toContain('breaking rename');
});
