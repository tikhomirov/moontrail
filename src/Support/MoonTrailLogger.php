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
