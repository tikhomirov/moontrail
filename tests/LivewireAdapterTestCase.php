<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests;

/**
 * Тестовое приложение без MoonShine: ui_adapter=livewire, провайдер MoonShine
 * не поднимается — как в реальном хосте, где пакет установлен без админки.
 */
class LivewireAdapterTestCase extends TestCase
{
    protected function uiAdapter(): string
    {
        return 'livewire';
    }

    protected function usesMoonShine(): bool
    {
        return false;
    }
}
