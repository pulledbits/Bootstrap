<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{
    public static function loader(callable $config): callable
    {
        $resourcesCache = [];
        return static function (string $identifier, mixed ...$args) use ($config, &$resourcesCache): mixed {
            if (array_key_exists($identifier, $resourcesCache) === false) {
                $validate = function (array $schema) use ($identifier, $config) {
                    return $config($identifier, $schema);
                };
                $resourcesCache[$identifier] = self::require(Bootstrap::resourcesPath($config) . DIRECTORY_SEPARATOR . $identifier . '.php', $validate, function (string $otherIdentifier, mixed ...$args) use ($identifier, $config): mixed {
                    if (str_starts_with($otherIdentifier, $identifier)) {
                        return Resource::loader($config)($otherIdentifier, ...$args);
                    }

                    if (substr_count($otherIdentifier, '/') === substr_count($identifier, '/')) {
                        return Resource::loader($config)($otherIdentifier, ...$args);
                    }

                    return null;
                });
            }
            return $resourcesCache[$identifier](...$args);
        };
    }

    /**
     * The purpose of the method is shielding other variables from the included script
     * @param string $path
     * @param callable $validate
     * @param callable $bootstrap
     * @return mixed
     * @noinspection PhpIncludeInspection PhpUnusedParameterInspection
     */
    private static function require(string $path, callable $validate, callable $bootstrap): callable
    {
        return (require $path);
    }
}