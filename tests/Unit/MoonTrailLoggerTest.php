<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use MoonShine\MoonTrail\MoonTrailServiceProvider;
use MoonShine\MoonTrail\Support\MoonTrailLogger;

it('writes structured runtime resolution log', function (): void {
    Log::spy();
    config()->set('moontrail.activity_logger', 'auto');

    $provider = new MoonTrailServiceProvider(app());
    $method = new ReflectionMethod($provider, 'logRuntimeResolution');
    $method->invoke($provider);

    Log::shouldHaveReceived('info')->withArgs(
        static fn (string $message, array $context): bool => str_contains($message, 'MoonTrail: auto resolved to')
            && isset($context['configured_driver'], $context['resolved_driver'], $context['activity_model']),
    );
});

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
