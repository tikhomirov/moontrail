<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Activity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Models\MoonTrailActivity;
use MoonShine\MoonTrail\Support\MoonTrailConfig;

/**
 * @implements ActivityQueryContract<Model>
 */
final class NullActivityQuery implements ActivityQueryContract
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = MoonTrailConfig::uiPerPage();

        return new Paginator(items: [], total: 0, perPage: $perPage, currentPage: 1);
    }

    public function find(int|string $id): ?ActivityRecordContract
    {
        return null;
    }

    public function stats(): array
    {
        return [
            'total'   => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'other'   => 0,
        ];
    }

    public function modelClass(): string
    {
        return MoonTrailActivity::class;
    }

    public function distinctValues(string $column): array
    {
        return [];
    }
}
