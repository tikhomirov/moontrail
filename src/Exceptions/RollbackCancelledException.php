<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Exceptions;

use RuntimeException;

/**
 * Thrown when a ModelRollingBack listener returns false,
 * cancelling the rollback before any data changes are made.
 */
final class RollbackCancelledException extends RuntimeException {}
