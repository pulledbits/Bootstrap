<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{

    private static $cache = [];

    /**
     * The purpose of the method is shielding other variables from the included script
     * @param string $path
     * @param callable $validate
     * @return mixed
     * @noinspection PhpIncludeInspection PhpUnusedParameterInspection
     */
    public static function require(string $path, callable $validate): callable
    {
        if (array_key_exists($path, self::$cache) === false) {
            self::$cache[$path] = (require $path);
        }
        return self::$cache[$path];
    }

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