<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Exceptions;

use RuntimeException;
use Throwable;

final class RollbackConflictException extends RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly string $reason = 'unknown',
    ) {
        parent::__construct($message, $code, $previous);
    }
}
