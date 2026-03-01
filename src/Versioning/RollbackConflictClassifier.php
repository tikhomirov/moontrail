<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Versioning;

use Illuminate\Database\QueryException;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use Throwable;

/**
 * Classifies a raw Throwable thrown inside the rollback transaction
 * into a structured RollbackConflictException with a machine-readable reason.
 *
 * Reasons:
 *  - db_constraint  : unique / foreign-key / not-null violation (QueryException with SQLSTATE 23xxx)
 *  - model_missing  : the target model record does not exist / was deleted
 *  - unknown        : anything else that is not a ValidationException or
 *                     ModelVersionNotFoundException (those are re-thrown as-is)
 */
final class RollbackConflictClassifier
{
    /**
     * Map a raw exception to a RollbackConflictException with semantic reason.
     * The caller is responsible for NOT passing ValidationException or
     * ModelVersionNotFoundException here – those should be re-thrown directly.
     */
    public static function classify(Throwable $e): RollbackConflictException
    {
        $reason = self::detectReason($e);

        return new RollbackConflictException(
            message: $e->getMessage(),
            code: $e->getCode() === 0 ? 0 : (int) $e->getCode(),
            previous: $e,
            reason: $reason,
        );
    }

    private static function detectReason(Throwable $e): string
    {
        if (! $e instanceof QueryException) {
            return 'unknown';
        }

        // Laravel's QueryException message starts with: SQLSTATE[<code>] ...
        // getSqlState() was added in Laravel 11.x; for older versions we parse the message.
        if (preg_match('/SQLSTATE\[(\w+)\]/i', $e->getMessage(), $matches) !== 1) {
            return 'unknown';
        }

        // SQLSTATE class 23 = Integrity Constraint Violation
        if (str_starts_with($matches[1], '23')) {
            return 'db_constraint';
        }

        return 'unknown';
    }
}
