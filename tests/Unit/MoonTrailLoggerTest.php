<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use MoonShine\MoonTrail\MoonTrailServiceProvider;
use MoonShine\MoonTrail\Support\MoonTrailLogger;

it('does not log runtime resolution when booting the provider', function (string $driver): void {
    Log::spy();
    config()->set('moontrail.activity_logger', $driver);
    config()->set('moontrail.ui_adapter', 'headless');

    $provider = new MoonTrailServiceProvider(app());
    $provider->register();
    $provider->boot();

    Log::shouldNotHaveReceived('debug');
    Log::shouldNotHaveReceived('info');
})->with(['spatie', 'auto', 'database', 'none']);

it('adds base component key to structured context', function (): void {
    Log::spy();

    $logger = app(MoonTrailLogger::class);
    $logger->info('test_event', ['foo' => 'bar']);

    Log::shouldHaveReceived('info')->withArgs(
        static fn (string $message, array $context): bool => $message === 'MoonTrail: test_event'
            && ($context['component'] ?? null) === 'moontrail'
            && ($context['foo'] ?? null) === 'bar',
    );
});
