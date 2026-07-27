<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Installer;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_values;
use function is_string;
use function preg_match;
use function sort;

final class ModelScanner
{
    /**
     * @return array<int, string>
     */
    public function scan(string $modelsPath): array
    {
        if (! is_dir($modelsPath)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelsPath));

        $models = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {

            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (! is_string($content)) {
                continue;
            }

            if (! preg_match('/^\s*namespace\s+([^;]+);/m', $content, $namespaceMatch)) {
                continue;
            }

            if (! preg_match('/^\s*(?:final\s+)?class\s+(\w+)/m', $content, $classMatch)) {
                continue;
            }

            $models[] = mb_trim($namespaceMatch[1]) . '\\' . mb_trim($classMatch[1]);
        }

        $models = array_values(array_unique($models));
        sort($models);

        return $models;
    }
}
