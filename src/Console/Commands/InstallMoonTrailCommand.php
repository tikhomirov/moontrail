<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MoonShine\MoonTrail\Installer\ConfigUpdater;
use MoonShine\MoonTrail\Installer\ModelScanner;
use MoonShine\MoonTrail\Installer\ResourcePatcher;
use MoonShine\MoonTrail\Installer\ResourceScanner;
use Throwable;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function class_exists;
use function file_exists;
use function filter_var;
use function in_array;
use function is_scalar;
use function is_string;
use function Laravel\Prompts\multiselect;
use function sprintf;

final class InstallMoonTrailCommand extends Command
{
    protected $signature = 'moontrail:install
                            {--force : Overwrite published files when possible}
                            {--safe= : Do not modify PHP files of resources/models}
                            {--auto-patch : Try to auto-patch MoonShine resources after confirmation}';

    protected $description = 'Interactive installer for MoonShine Logs package';

    /**
     * @var array<int, string>
     */
    private array $publishSteps = [];

    private bool $migrationsExecuted = false;

    /**
     * @var array<int, string>
     */
    private array $selectedModels = [];

    /**
     * @var array<int, string>
     */
    private array $resourcesUpdated = [];

    /**
     * @var array<int, string>
     */
    private array $resourcesManual = [];

    public function __construct(
        private readonly ModelScanner $modelScanner,
        private readonly ConfigUpdater $configUpdater,
        private readonly ResourceScanner $resourceScanner,
        private readonly ResourcePatcher $resourcePatcher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $safeMode = $this->resolveSafeMode();

        if (! $this->checkMoonShineInstalled() || ! $this->checkDatabaseConnection() || ! $this->confirmProduction()) {
            return self::FAILURE;
        }

        $this->handlePublishing();
        $this->handleMigrations();
        $this->handleModelSelection();
        $this->handleResourceSelection($safeMode);

        $this->printSummary($safeMode);

        return self::SUCCESS;
    }

    private function resolveSafeMode(): bool
    {
        if ((bool) $this->option('auto-patch')) {
            return false;
        }

        $default = (bool) config('moontrail.installer.safe_mode_default', true);

        $raw = $this->option('safe');

        if ($raw === null) {
            return $default;
        }

        if (is_bool($raw)) {
            return $raw;
        }

        if (! is_scalar($raw)) {
            return $default;
        }

        $parsed = filter_var((string) $raw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    private function checkMoonShineInstalled(): bool
    {
        if (! class_exists(\MoonShine\Laravel\Providers\MoonShineServiceProvider::class)) {
            $this->components->error('MoonShine package was not detected. Install moonshine/moonshine first.');

            return false;
        }

        $this->components->info('MoonShine detected');

        return true;
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $this->components->error('Database connection failed. Check DB_* env variables and retry.');
            $this->line($exception->getMessage());

            return false;
        }

        $this->components->info('DB connection ok');

        return true;
    }

    private function confirmProduction(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('Application is in production. Run installer interactively to confirm.');

            return false;
        }

        return $this->confirm('App is in production. Continue?', false);
    }

    private function handlePublishing(): void
    {
        $steps = [
            'config' => 'moontrail-config',
            'views'  => 'moontrail-views',
            'lang'   => 'moontrail-lang',
            'assets' => 'moontrail-assets',
        ];

        $assetsPublished = false;

        foreach ($steps as $label => $tag) {
            if (! $this->shouldPublishStep($label)) {
                continue;
            }

            $exitCode = Artisan::call('vendor:publish', [
                '--tag'   => $tag,
                '--force' => (bool) $this->option('force'),
            ]);

            if ($exitCode === self::SUCCESS) {
                $this->publishSteps[] = $label;
                $this->components->info(sprintf('Published %s', $label));

                if ($label === 'assets') {
                    $assetsPublished = true;
                }
            }
        }

        if ($assetsPublished) {
            Artisan::call('optimize:clear');
            $this->components->info('Cache cleared to ensure fresh assets');
        }
    }

    private function handleMigrations(): void
    {
        if (! $this->shouldRunMigrationsStep()) {
            return;
        }

        if (app()->environment('production') && ! $this->confirm('Run migrations in production with --force?', false)) {
            return;
        }

        $exitCode = Artisan::call('migrate', ['--force' => true]);

        if ($exitCode === self::SUCCESS) {
            $this->migrationsExecuted = true;
            $this->components->info('Migrations completed');
        }
    }

    private function handleModelSelection(): void
    {
        $candidates = $this->discoverCandidateModels();
        $defaults = $this->discoverDefaultModels();

        if ($candidates === []) {
            $this->components->warn('No models found in app/Models.');

            return;
        }

        if ($this->input->isInteractive()) {
            $selected = multiselect(
                label: 'Select models to track',
                options: $candidates,
                default: $defaults,
            );

            $this->selectedModels = array_values(array_filter(
                array_map(static fn (int|string $value): string => (string) $value, $selected),
                static fn (string $value): bool => $value !== '',
            ));
        } else {
            $this->selectedModels = $defaults;
        }

        if ($this->selectedModels === []) {
            $this->components->warn('No models selected for tracking.');

            return;
        }

        $configPath = config_path('moontrail.php');

        if (! file_exists($configPath)) {
            $this->handleMissingConfigFile();

            return;
        }

        if ($this->configUpdater->updateTrackedModels($configPath, $this->selectedModels)) {
            $this->components->info('Updated auto_track_models and tracked_models in config.');

            return;
        }

        $this->components->warn('Could not update config automatically. Apply snippet manually:');
        $this->line($this->configUpdater->buildManualSnippet($this->selectedModels));
    }

    private function handleMissingConfigFile(): void
    {
        if ($this->shouldPublishMissingConfigStep()) {
            $exitCode = Artisan::call('vendor:publish', [
                '--tag'   => 'moontrail-config',
                '--force' => (bool) $this->option('force'),
            ]);

            if ($exitCode === self::SUCCESS) {
                $this->publishSteps[] = 'config';

                $configPath = config_path('moontrail.php');

                if ($this->configUpdater->updateTrackedModels($configPath, $this->selectedModels)) {
                    $this->components->info('Config published and updated.');

                    return;
                }
            }
        }

        $this->components->warn('Config file is missing. Add this snippet manually to moontrail config:');
        $this->line($this->configUpdater->buildManualSnippet($this->selectedModels));
    }

    private function handleResourceSelection(bool $safeMode): void
    {
        $resources = $this->resourceScanner->scan(base_path('app/MoonShine/Resources'));

        if ($resources === []) {
            $this->components->warn('No MoonShine resources found in app/MoonShine/Resources.');

            return;
        }

        $defaults = array_values(array_map(
            static fn (array $resource): string => $resource['name'],
            array_filter(
                $resources,
                fn (array $resource): bool => in_array($resource['model'], $this->selectedModels, true),
            ),
        ));

        $choices = array_values(array_map(static fn (array $resource): string => $resource['name'], $resources));

        $selectedNames = $this->input->isInteractive()
            ? multiselect(
                label: 'Select resources for History tab',
                options: $choices,
                default: $defaults,
            )
            : $defaults;

        $selectedNames = array_values(array_filter(
            array_map(static fn (int|string $value): string => (string) $value, $selectedNames),
            static fn (string $value): bool => $value !== '',
        ));

        if ($selectedNames === []) {
            return;
        }

        $selectedResources = array_values(array_filter(
            $resources,
            static fn (array $resource): bool => in_array($resource['name'], $selectedNames, true),
        ));

        if ($safeMode) {
            foreach ($selectedResources as $resource) {
                $this->resourcesManual[] = $resource['name'];
                $this->printManualResourceInstructions($resource['class']);
            }

            return;
        }

        if (! $this->confirmAutoPatch()) {
            foreach ($selectedResources as $resource) {
                $this->resourcesManual[] = $resource['name'];
                $this->printManualResourceInstructions($resource['class']);
            }

            return;
        }

        foreach ($selectedResources as $resource) {
            if ($this->resourcePatcher->patch($resource['path'])) {
                $this->resourcesUpdated[] = $resource['name'];
                continue;
            }

            $this->resourcesManual[] = $resource['name'];
            $this->printManualResourceInstructions($resource['class']);
        }
    }

    private function confirmAutoPatch(): bool
    {
        if (! (bool) $this->option('auto-patch')) {
            return false;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->confirm('Auto-patch can modify resource PHP files. Continue?', false);
    }

    private function printManualResourceInstructions(string $resourceClass): void
    {
        $this->line("Manual steps for {$resourceClass}:");
        $this->line('1) add: use MoonShine\\MoonTrail\\Traits\\WithMoonTrailTab;');
        $this->line('2) add trait in class: use WithMoonTrailTab;');
        $this->line('   The history tab will appear automatically on the detail page.');
    }

    /**
     * @return array<int, string>
     */
    private function discoverCandidateModels(): array
    {
        $models = $this->modelScanner->scan(base_path('app/Models'));

        foreach ($this->discoverDefaultModels() as $defaultModel) {
            $models[] = $defaultModel;
        }

        return array_values(array_unique($models));
    }

    /**
     * @return array<int, string>
     */
    private function discoverDefaultModels(): array
    {
        $defaults = (array) config('moontrail.installer.default_models', [
            'App\\Models\\User',
            \MoonShine\Laravel\Models\MoonshineUser::class,
            'App\\Models\\Role',
            'MoonShine\\Laravel\\Models\\Role',
        ]);

        $resolved = [];

        foreach ($defaults as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            if (! class_exists($candidate)) {
                continue;
            }

            $resolved[] = $candidate;
        }

        return $resolved;
    }

    private function shouldPublishStep(string $label): bool
    {
        if ($this->input->isInteractive()) {
            return $this->confirm(sprintf('Publish %s?', $label), false);
        }

        return (bool) config("moontrail.installer.non_interactive.publish_{$label}", false);
    }

    private function shouldRunMigrationsStep(): bool
    {
        if ($this->input->isInteractive()) {
            return $this->confirm('Run migrations now?', false);
        }

        return (bool) config('moontrail.installer.non_interactive.run_migrations', false);
    }

    private function shouldPublishMissingConfigStep(): bool
    {
        if ($this->input->isInteractive()) {
            return $this->confirm('Config is not published. Publish config now?', true);
        }

        return (bool) config('moontrail.installer.non_interactive.publish_config', false);
    }

    private function printSummary(bool $safeMode): void
    {
        $this->newLine();
        $this->components->info('Installation summary');
        $this->line('Publish steps: ' . ($this->publishSteps === [] ? 'none' : implode(', ', $this->publishSteps)));
        $this->line('Migrations: ' . ($this->migrationsExecuted ? 'executed' : 'skipped'));
        $this->line('Tracked models: ' . ($this->selectedModels === [] ? 'none' : implode(', ', $this->selectedModels)));
        $this->line('Resources updated: ' . ($this->resourcesUpdated === [] ? 'none' : implode(', ', $this->resourcesUpdated)));
        $this->line('Resources requiring manual changes: ' . ($this->resourcesManual === [] ? 'none' : implode(', ', $this->resourcesManual)));
        $this->line('Mode: ' . ($safeMode ? 'SAFE' : 'AUTO-PATCH'));

        $this->newLine();
        $this->line('Next checks:');
        $this->line('1) Hard refresh browser (Ctrl+Shift+R) to load fresh CSS');
        $this->line('2) Open /admin/resource/moon-trail-resource/moon-trail-index-page');
        $this->line('3) Check storage/logs/laravel.log');
        $this->line('4) Run composer ci');
    }
}
