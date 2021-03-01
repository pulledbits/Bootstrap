<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{
    public static function generate(string $resourcesPath, string $resourceNSPath, callable $writer): void
    {
        foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . $resourceNSPath . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                self::generate($resourcesPath, trim(str_replace($resourcesPath, '', $resourceFilePath) . DIRECTORY_SEPARATOR, '/\\'), $writer);
                continue;
            }
            if (str_ends_with($resourceFilePath, '.php')) {
                $writer($resourceNSPath, $resourceFilePath);
            }
        }
    }
}