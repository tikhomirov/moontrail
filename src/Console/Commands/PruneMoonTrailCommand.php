<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Support\ActivityModelResolver;
use MoonShine\MoonTrail\Support\MoonTrailConfig;
use MoonShine\MoonTrail\Support\MoonTrailLogger;
use RuntimeException;

final class PruneMoonTrailCommand extends Command
{
    protected $signature = 'moontrail:prune
                            {--days= : Remove records older than this many days}
                            {--model= : Only prune versions for a specific model class}
                            {--versions-only : Only prune model_versions, keep activity_log}
                            {--activity-only : Only prune activity_log, keep model_versions}';

    protected $description = 'Prune old activity log records and model versions';

    public function __construct(
        private readonly ActivityModelResolver $activityModelResolver,
        private readonly MoonTrailLogger $logger,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = $this->resolveDays();
        $modelClass = $this->option('model');
        $versionsOnly = (bool) $this->option('versions-only');
        $activityOnly = (bool) $this->option('activity-only');
        $this->validateFlags($versionsOnly, $activityOnly);

        $cutoff = now()->subDays($days);
        $modelScope = is_string($modelClass) ? $modelClass : null;
        $versionsDeleted = 0;
        $activitiesDeleted = 0;

        if (! $activityOnly) {
            $versionsDeleted = $this->pruneModelVersions($cutoff, $modelScope);
        }

        if (! $versionsOnly) {
            $activitiesDeleted = $this->pruneActivityLog($cutoff, $modelScope);
        }

        $this->logger->info('prune_summary', [
            'days'                 => $days,
            'cutoff'               => $cutoff->format(DateTimeInterface::ATOM),
            'model_scope'          => $modelScope,
            'versions_only'        => $versionsOnly,
            'activity_only'        => $activityOnly,
            'model_versions_count' => $versionsDeleted,
            'activity_count'       => $activitiesDeleted,
        ]);

        return self::SUCCESS;
    }

    private function pruneModelVersions(DateTimeInterface $cutoff, ?string $modelClass): int
    {
        $query = ModelVersion::query()->where('created_at', '<', $cutoff);

        if ($modelClass !== null) {
            $query->where('versionable_type', $modelClass);
        }

        $count = $query->count();
        $query->delete();

        $this->components->info("Pruned {$count} model version(s) older than {$cutoff->format('Y-m-d')}.");

        return $count;
    }

    private function pruneActivityLog(DateTimeInterface $cutoff, ?string $modelClass): int
    {
        $activityModel = $this->activityModelResolver->resolveModelClass();
        /** @var class-string<Model> $activityModel */
        $query = $activityModel::query()->where('created_at', '<', $cutoff);
        $table = (new $activityModel)->getTable();

        if ($modelClass !== null) {
            $query->where(static function ($builder) use ($modelClass, $table): void {
                $builder->where('subject_type', $modelClass);

                if (Schema::hasColumn($table, 'model_type')) {
                    $builder->orWhere('model_type', $modelClass);
                }
            });
        }

        $count = $query->count();
        $query->delete();

        $this->components->info("Pruned {$count} activity log record(s) older than {$cutoff->format('Y-m-d')}.");

        return $count;
    }

    private function validateFlags(bool $versionsOnly, bool $activityOnly): void
    {
        if ($versionsOnly && $activityOnly) {
            throw new RuntimeException('moontrail:prune options --versions-only and --activity-only are mutually exclusive.');
        }
    }

    private function resolveDays(): int
    {
        $rawDays = $this->option('days');
        $configuredDays = MoonTrailConfig::pruningDays();
        $value = is_numeric($rawDays) ? (int) $rawDays : $configuredDays;

        if ($value <= 0) {
            throw new RuntimeException('moontrail:prune option --days must be a positive integer (> 0).');
        }

        return $value;
    }
}
