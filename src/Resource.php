<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{
    public static function loader(callable $config): callable
    {
        $resourcesCache = [];
        return static function (string $identifier, mixed ...$args) use ($config, &$resourcesCache): mixed {
            if (array_key_exists($identifier, $resourcesCache) === false) {
                $resourcesCache[$identifier] = self::require(Bootstrap::resourcesPath($config) . DIRECTORY_SEPARATOR . $identifier . '.php', $config($identifier, []), Resource::loader($config));
            }
            return $resourcesCache[$identifier](...$args);
        };
    }

    /**
     * The purpose of the method is shielding other variables from the included script
     * @param string $path
     * @param array $configuration
     * @param callable $bootstrap
     * @return mixed
     * @noinspection PhpIncludeInspection PhpUnusedParameterInspection
     */
    private static function require(string $path, array $configuration, callable $bootstrap): callable
    {
        return (require $path);
    }
}