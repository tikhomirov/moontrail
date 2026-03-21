<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Applies ActivityLogFilterData to an Eloquent query builder for Activity.
 */
final class ActivityLogQuery
{
    /**
     * @param Builder<Activity> $builder
     * @return Builder<Activity>
     */
    public function apply(Builder $builder, ActivityLogFilterData $filters): Builder
    {
        if ($filters->logName !== null) {
            $builder->where('log_name', $filters->logName);
        }

        if ($filters->event !== null) {
            $builder->where('event', $filters->event);
        }

        if ($filters->subjectType !== null) {
            $builder->where('subject_type', $filters->subjectType);
        }

        if ($filters->subjectId !== null) {
            $builder->where('subject_id', $filters->subjectId);
        }

        if ($filters->causerType !== null) {
            $builder->where('causer_type', $filters->causerType);
        }

        if ($filters->causerId !== null) {
            $builder->where('causer_id', $filters->causerId);
        }

        $dateFrom = $this->parseDate($filters->dateFrom);

        if ($dateFrom instanceof Carbon) {
            $builder->where('created_at', '>=', $dateFrom->startOfDay());
        }

        $dateUntil = $this->parseDate($filters->dateUntil);

        if ($dateUntil instanceof Carbon) {
            $builder->where('created_at', '<=', $dateUntil->endOfDay());
        }

        if ($filters->search !== null) {
            $search = $filters->search;
            $like = '%' . $search . '%';

            preg_match_all('/\d+/', $search, $numericMatches);
            /** @var list<string> $numericIds */
            $numericIds = array_unique($numericMatches[0]);

            $builder->where(static function (Builder $query) use ($like, $numericIds): void {
                $query
                    ->where('description', 'like', $like)
                    ->orWhere('event', 'like', $like)
                    ->orWhere('subject_type', 'like', $like)
                    ->orWhere('log_name', 'like', $like)
                    ->orWhere('properties', 'like', $like);

                if ($numericIds !== []) {
                    $query->orWhereIn('subject_id', $numericIds)
                        ->orWhereIn('causer_id', $numericIds);
                }
            });
        }

        return $builder;
    }

    private function parseDate(?string $raw): ?Carbon
    {
        if ($raw === null) {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $raw);

            if (! $parsed instanceof Carbon || $parsed->format('Y-m-d') !== $raw) {
                return null;
            }

            return $parsed;
        } catch (Throwable) {
            return null;
        }
    }
}
