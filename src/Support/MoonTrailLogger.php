<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

final class MoonTrailLogger
{
    /**
     * @param array<string, mixed> $context
     */
    public function info(string $event, array $context = []): void
    {
        Log::info($this->message($event), $this->withBaseContext($context));
    }

    /**
     * Диагностический уровень: в production-логе по умолчанию не виден.
     *
     * @param array<string, mixed> $context
     */
    public function debug(string $event, array $context = []): void
    {
        Log::debug($this->message($event), $this->withBaseContext($context));
    }

    /**
     * Сообщает, какой драйвер журналирования выбран на старте приложения.
     *
     * Уровень debug, не info: метод вызывается на каждом boot (то есть на каждом
     * HTTP-запросе), и на info эта строка засоряла production-лог.
     */
    public function runtimeResolution(ActivityRuntime $runtime): void
    {
        $this->debug(
            $runtime->configuredDriver === 'auto'
                ? sprintf('auto resolved to "%s" driver', $runtime->resolvedDriver)
                : 'runtime_resolved',
            [
                'configured_driver' => $runtime->configuredDriver,
                'resolved_driver'   => $runtime->resolvedDriver,
                'activity_model'    => $runtime->activityModel,
                'resolution_mode'   => $runtime->configuredDriver === 'auto' ? 'auto_detected' : 'explicit',
            ],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $event, array $context = []): void
    {
        Log::warning($this->message($event), $this->withBaseContext($context));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $event, array $context = []): void
    {
        Log::error($this->message($event), $this->withBaseContext($context));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function withException(Throwable $exception, array $context = []): array
    {
        return $context + [
            'exception' => $exception::class,
            'message'   => $exception->getMessage(),
            'code'      => $exception->getCode(),
        ];
    }

    private function message(string $event): string
    {
        return 'MoonTrail: ' . $event;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function withBaseContext(array $context): array
    {
        return $context + ['component' => 'moontrail'];
    }
}
