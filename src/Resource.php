<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{
    private static array $closures = [];

    public static function generate(string $resourcesPath, string $path, callable $writer): void
    {
        self::recurse($resourcesPath, $path, '', $writer);
    }

    private static function recurse(string $baseDirectory, string $path, string $namespace, callable $writer): void
    {
        foreach (glob($baseDirectory . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '') . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                self::recurse($baseDirectory, trim($path . '/' . basename($resourceFilePath), '/'), trim($namespace . '\\' . basename($resourceFilePath), '\\'), $writer);
            } elseif (str_ends_with($resourceFilePath, '.php')) {
                $writer($resourceFilePath, $namespace);
            }
        }
    }

    public static function open(string $resourcePath, bool $cache = true): mixed
    {
        if (!isset(self::$closures[$resourcePath]) || $cache === false) {
            self::$closures[$resourcePath] = require $resourcePath;
        }
        return self::$closures[$resourcePath];
    }
}