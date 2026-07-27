<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Installer;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function is_string;
use function mb_ltrim;
use function preg_match;

final class ResourceScanner
{
    /**
     * @return array<int, array{name: string, class: string, model: string, path: string}>
     */
    public function scan(string $resourcesPath): array
    {
        if (! is_dir($resourcesPath)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resourcesPath));
        $resources = [];

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

            if (! preg_match('/protected\s+string\s+\$model\s*=\s*([^;]+)::class\s*;/m', $content, $modelMatch)) {
                continue;
            }

            $namespace = mb_trim($namespaceMatch[1]);
            $className = mb_trim($classMatch[1]);
            $model = mb_ltrim(mb_trim($modelMatch[1]), '\\');

            if (! str_contains($model, '\\')) {
                $model = $namespace . '\\' . $model;
            }

            $resources[] = [
                'name'  => $className,
                'class' => $namespace . '\\' . $className,
                'model' => $model,
                'path'  => $file->getPathname(),
            ];
        }

        usort($resources, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        return $resources;
    }
}
