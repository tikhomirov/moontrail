<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use MoonShine\MoonTrail\Versioning\RollbackConflictClassifier;

it('classifies a db constraint violation as db_constraint reason', function (): void {
    $queryException = new QueryException(
        connectionName: 'testing',
        sql: 'INSERT INTO test_posts',
        bindings: [],
        previous: new \PDOException('SQLSTATE[23000]: Integrity constraint violation', 1062),
    );

    $result = RollbackConflictClassifier::classify($queryException);

    expect($result)->toBeInstanceOf(RollbackConflictException::class)
        ->and($result->reason)->toBe('db_constraint')
        ->and($result->getPrevious())->toBe($queryException);
});

it('classifies a generic runtime exception as unknown reason', function (): void {
    $generic = new \RuntimeException('Something went wrong');

    $result = RollbackConflictClassifier::classify($generic);

    expect($result)->toBeInstanceOf(RollbackConflictException::class)
        ->and($result->reason)->toBe('unknown')
        ->and($result->getPrevious())->toBe($generic);
});

it('preserves original message in classified exception', function (): void {
    $original = new \RuntimeException('original error message');

    $result = RollbackConflictClassifier::classify($original);

    expect($result->getMessage())->toBe('original error message');
});
