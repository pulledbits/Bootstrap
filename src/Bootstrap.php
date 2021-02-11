<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Closure;
use ReflectionAttribute;
use ReflectionException;
use ReflectionFunction;


final class Bootstrap
{
    public static function load(string $configurationPath): Closure
    {
        $config = static function (string $section, array $schema) use ($configurationPath) {
            return Configuration::open($configurationPath, $section, $schema);
        };

        $path = static function (string $identifier) use ($config): string {
            $configuration = $config('BOOTSTRAP', ['path' => ['url', ['default' => '%configuration-path%' . DIRECTORY_SEPARATOR . 'bootstrap']]]);
            return $configuration['path'] . DIRECTORY_SEPARATOR . $identifier . '.php';
        };

        $resourcesCache = [];
        $resources = static function (string $identifier) use ($config, $path, &$resourcesCache): callable {
            if (array_key_exists($identifier, $resourcesCache)) {
                return $resourcesCache[$identifier];
            }
            return $resourcesCache[$identifier] = self::require($path($identifier), $config($identifier, []));
        };

        $bootstrap = static function (string $identifier) use ($resources, &$bootstrap) {
            return $resources($identifier)(...self::resourceArguments($bootstrap, $resources($identifier)));
        };

        return $bootstrap;
    }

    public static function resourceArguments(callable $bootstrap, Closure $resource): array
    {
        try {
            $reflection = new ReflectionFunction($resource);
            if ($reflection->getNumberOfParameters() === 0) {
                return [];
            }
        } catch (ReflectionException $e) {
            trigger_error($e->getMessage());
            return [];
        }

        $arguments = [];
        $attributes = $reflection->getAttributes();
        array_walk($attributes, static function (ReflectionAttribute $attribute) use ($bootstrap, &$arguments) {
            $arguments = array_merge($arguments, match ($attribute->getName()) {
                Dependency::class => array_map($bootstrap, $attribute->getArguments())
            });
        });
        return $arguments;
    }

    /** @noinspection PhpIncludeInspection PhpUnusedParameterInspection */
    private static function require(string $path, array $configuration): callable
    {
        return require $path;
    }
}
