<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;

it('caches all registered views when moonshine is not installed', function (): void {
    // Эмулируем отсутствующий moonshine: снимаем его view-namespace, как если бы
    // пакет не был установлен. Пока moonshine-blade'ы лежали в общем namespace
    // пакета, `artisan view:cache` падал на x-moonshine::icon.
    View::replaceNamespace('moonshine', []);

    $this->artisan('view:cache')->assertSuccessful();
});
