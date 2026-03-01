<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Installer;

use function array_unique;
use function array_values;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_string;
use function preg_replace;

final class ConfigUpdater
{
    /**
     * @param array<int, string> $models
     */
    public function updateTrackedModels(string $configPath, array $models): bool
    {
        if (! file_exists($configPath)) {
            return false;
        }

        $content = file_get_contents($configPath);

        if (! is_string($content)) {
            return false;
        }

        $models = array_values(array_unique($models));
        $replacement = $this->buildArrayBlock($models);

        $updated = preg_replace(
            pattern: "/'auto_track_models'\\s*=>\\s*\\[[\\s\\S]*?\\],/",
            replacement: "'auto_track_models' => {$replacement},",
            subject: $content,
            limit: 1,
        );

        if (! is_string($updated)) {
            return false;
        }

        $updated = preg_replace(
            pattern: "/'tracked_models'\\s*=>\\s*\\[[\\s\\S]*?\\],/",
            replacement: "'tracked_models' => {$replacement},",
            subject: $updated,
            limit: 1,
        );

        if (! is_string($updated)) {
            return false;
        }

        return file_put_contents($configPath, $updated) !== false;
    }

    /**
     * @param array<int, string> $models
     */
    public function buildManualSnippet(array $models): string
    {
        $block = $this->buildArrayBlock($models);

        return "'auto_track_models' => {$block},\n'tracked_models' => {$block},";
    }

    /**
     * @param array<int, string> $models
     */
    private function buildArrayBlock(array $models): string
    {
        if ($models === []) {
            return '[]';
        }

        $lines = [
            '[',
        ];

        foreach ($models as $model) {
            $lines[] = "        '{$model}',";
        }

        $lines[] = '    ]';

        return implode("\n", $lines);
    }
}
