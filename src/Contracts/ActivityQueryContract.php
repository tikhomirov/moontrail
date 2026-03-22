<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TActivity of Model
 */
interface ActivityQueryContract
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, TActivity>
     */
    public function paginate(array $filters): LengthAwarePaginator;

    public function find(int|string $id): ?ActivityRecordContract;

    /**
     * @return array{total: int, created: int, updated: int, deleted: int, other: int}
     */
    public function stats(): array;

    /**
     * @return class-string<TActivity>
     */
    public function modelClass(): string;

    /**
     * @param non-empty-string $column
     * @return list<string>
     */
    public function distinctValues(string $column): array;
}
