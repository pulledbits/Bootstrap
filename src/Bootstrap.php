<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Closure;
use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;


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
        $resources = static function (string $identifier) use ($path, &$resourcesCache): callable {
            if (array_key_exists($identifier, $resourcesCache)) {
                return $resourcesCache[$identifier];
            }
            /** @noinspection PhpIncludeInspection */
            return $resourcesCache[$identifier] = require $path($identifier);
        };

        $bootstrap = static function (string $identifier) use ($config, $resources, &$bootstrap) {
            return $resources($identifier)(...self::resourceArguments($bootstrap, $resources($identifier), static function () use ($identifier, $config) {
                return $config($identifier, []);
            }));
        };

        return $bootstrap;
    }

    public static function resourceArguments(callable $bootstrap, Closure $resource, callable $resourceConfig): array
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
        $firstParameter = $reflection->getParameters()[0];
        if (self::isConfigurationArgument($firstParameter)) {
            $arguments[$firstParameter->getName()] = $resourceConfig();
        }
        if ($reflection->getNumberOfParameters() === count($arguments)) {
            return $arguments;
        }
        $attributes = $reflection->getAttributes();
        array_walk($attributes, static function (ReflectionAttribute $attribute) use ($bootstrap, &$arguments) {
            $arguments = array_merge($arguments, match ($attribute->getName()) {
                Dependency::class => array_map($bootstrap, $attribute->getArguments())
            });
        });
        return $arguments;
    }

    #[Pure] public static function isConfigurationArgument(ReflectionParameter $firstParameter): bool
    {
        $firstParameterType = $firstParameter->getType();
        $firstParameterName = $firstParameter->getName();
        if (is_null($firstParameterType)) {
            if ($firstParameterName === 'configuration') {
                return true;
            }
        } elseif ($firstParameterType->getName() === 'array') {
            return true;
        }
        return false;
    }
}
