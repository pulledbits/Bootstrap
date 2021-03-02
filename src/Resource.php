<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{
    public static function generate(array $resourcesPaths, callable $writer): void
    {
        foreach ($resourcesPaths as $resourcesPath) {
            self::recurse($resourcesPath, '', $writer);
        }
    }

    private static function recurse(string $resourcesPath, string $resourceNSPath, callable $writer): void
    {
        foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . $resourceNSPath . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                self::recurse($resourcesPath, trim(str_replace($resourcesPath, '', $resourceFilePath) . DIRECTORY_SEPARATOR, '/\\'), $writer);
                continue;
            }
            if (str_ends_with($resourceFilePath, '.php')) {
                $writer($resourceNSPath, $resourceFilePath);
            }
        }
    }
}