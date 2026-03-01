<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use MoonShine\MoonTrail\Models\ModelVersion;
use Spatie\Activitylog\Models\Activity;

final class PruneMoonTrailCommand extends Command
{
    protected $signature = 'moontrail:prune
                            {--days=90 : Remove records older than this many days}
                            {--model= : Only prune versions for a specific model class}
                            {--versions-only : Only prune model_versions, keep activity_log}
                            {--activity-only : Only prune activity_log, keep model_versions}';

    protected $description = 'Prune old activity log records and model versions';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $modelClass = $this->option('model');
        $versionsOnly = (bool) $this->option('versions-only');
        $activityOnly = (bool) $this->option('activity-only');

        $cutoff = now()->subDays($days);

        if (! $activityOnly) {
            $this->pruneModelVersions($cutoff, is_string($modelClass) ? $modelClass : null);
        }

        if (! $versionsOnly) {
            $this->pruneActivityLog($cutoff, is_string($modelClass) ? $modelClass : null);
        }

        return self::SUCCESS;
    }

    private function pruneModelVersions(DateTimeInterface $cutoff, ?string $modelClass): void
    {
        $query = ModelVersion::query()->where('created_at', '<', $cutoff);

        if ($modelClass !== null) {
            $query->where('versionable_type', $modelClass);
        }

        $count = $query->count();
        $query->delete();

        $this->components->info("Pruned {$count} model version(s) older than {$cutoff->format('Y-m-d')}.");
    }

    private function pruneActivityLog(DateTimeInterface $cutoff, ?string $modelClass): void
    {
        $query = Activity::query()->where('created_at', '<', $cutoff);

        if ($modelClass !== null) {
            $query->where('subject_type', $modelClass);
        }

        $count = $query->count();
        $query->delete();

        $this->components->info("Pruned {$count} activity log record(s) older than {$cutoff->format('Y-m-d')}.");
    }
}
