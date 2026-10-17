<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Console\Commands;

use Illuminate\Console\Command;

final class InstallMoonTrailCommand extends Command
{
    protected $signature = 'moontrail:install {--ui= : moonshine, livewire, headless} {--logger= : spatie, database, none}';

    protected $description = 'Publish MoonTrail configuration and migrations without modifying application code';

    public function handle(): int
    {
        $ui = $this->option('ui') ?: ($this->input->isInteractive() ? $this->choice('UI', ['headless', 'moonshine', 'livewire'], 'headless') : 'headless');
        $logger = $this->option('logger') ?: ($this->input->isInteractive() ? $this->choice('Activity logger', ['spatie', 'database', 'none'], 'none') : 'none');

        if (! in_array($ui, ['headless', 'moonshine', 'livewire'], true) || ! in_array($logger, ['spatie', 'database', 'none'], true)) {
            $this->error('Unsupported MoonTrail UI or logger.');

            return self::FAILURE;
        }

        if ($ui === 'moonshine' && ! class_exists(\MoonShine\Laravel\MoonShineAuth::class)) {
            $this->error('Install moonshine/moonshine before choosing MoonShine UI.');

            return self::FAILURE;
        }

        if ($ui === 'livewire' && ! class_exists(\Livewire\Livewire::class)) {
            $this->error('Install livewire/livewire before choosing Livewire UI.');

            return self::FAILURE;
        }

        if ($logger === 'spatie' && ! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            $this->error('Install spatie/laravel-activitylog before choosing Spatie logging.');

            return self::FAILURE;
        }

        $configPath = config_path('moontrail.php');

        if (file_exists($configPath)) {
            $this->warn('Existing config/moontrail.php was not overwritten. Set ui_adapter and activity_logger manually.');
        } else {
            $config = file_get_contents(__DIR__ . '/../../../config/moontrail.php');

            if ($config === false) {
                $this->error('Could not read MoonTrail configuration template.');

                return self::FAILURE;
            }
            $config = str_replace("'ui_adapter' => 'headless'", "'ui_adapter' => '{$ui}'", $config);
            $config = str_replace("env('MOONTRAIL_LOGGER', 'auto')", "env('MOONTRAIL_LOGGER', '{$logger}')", $config);
            file_put_contents($configPath, $config);
            $this->info('Published config/moontrail.php.');
        }

        $migrations = ['2024_01_01_000001_create_model_versions_table.php'];

        if ($logger === 'database') {
            $migrations[] = '2024_01_01_000002_create_moontrail_activity_log_table.php';
            $migrations[] = '2026_03_22_000003_align_moontrail_activity_log_schema.php';
            $migrations[] = '2026_03_22_000004_optimize_moontrail_activity_log_indexes.php';
        }

        foreach ($migrations as $migration) {
            $destination = database_path('migrations/' . $migration);

            if (! file_exists($destination)) {
                copy(__DIR__ . '/../../../database/migrations/' . $migration, $destination);
                $this->info("Published {$migration}.");
            }
        }

        $this->components->info('No migrations were run and no application code was changed.');
        $this->line('Next: configure the model policy, add HasMoonTrail to selected models, embed the selected UI, then run migrations in a safe deployment step.');

        return self::SUCCESS;
    }
}
