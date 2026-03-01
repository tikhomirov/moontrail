<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Installer;

use function is_string;
use function preg_match;
use function preg_replace;

final class ResourcePatcher
{
    public function patch(string $resourcePath): bool
    {
        $content = file_get_contents($resourcePath);

        if (! is_string($content)) {
            return false;
        }

        $updated = $this->ensureTraitImport($content);

        $updated = $this->ensureTraitUsage($updated);

        if ($updated === null) {
            return false;
        }

        if ($updated === $content) {
            return true;
        }

        return file_put_contents($resourcePath, $updated) !== false;
    }

    private function ensureTraitImport(string $content): string
    {
        $traitImport = "use MoonShine\\MoonTrail\\Traits\\WithMoonTrailTab;\n";

        if (str_contains($content, $traitImport)) {
            return $content;
        }

        if (preg_match('/^namespace\\s+[^;]+;\\n\\n/m', $content) === 1) {
            $updated = preg_replace('/^namespace\\s+[^;]+;\\n\\n/m', "$0{$traitImport}", $content, 1);

            return is_string($updated) ? $updated : $content;
        }

        return $content;
    }

    private function ensureTraitUsage(string $content): ?string
    {
        if (str_contains($content, 'use WithMoonTrailTab;')) {
            return $content;
        }

        $updated = preg_replace('/((?:final\\s+|abstract\\s+)?class\\s+\\w+[^\\{]*\\{\\n)/', "$1    use WithMoonTrailTab;\n\n", $content, 1);

        return is_string($updated) ? $updated : null;
    }
}
