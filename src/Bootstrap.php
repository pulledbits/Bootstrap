<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Closure;
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

        $bootstrap = static function (string $identifier) use (&$bootstrap, $config, $resources) {
            $arguments = [];
            try {
                $reflection = new ReflectionFunction($resources($identifier));
                if ($reflection->getNumberOfParameters() > 0) {
                    $firstParameter = $reflection->getParameters()[0];
                    if (self::resourceRequiresConfigurationParameter($firstParameter)) {
                        $arguments[$firstParameter->getName()] = $config($identifier, []);
                    }

                    if ($reflection->getNumberOfParameters() > count($arguments)) { // multiple parameters
                        $attributes = $reflection->getAttributes();
                        $dependencyArguments = [];
                        foreach ($attributes as $attribute) {
                            match ($attribute->getName()) {
                                Dependency::class => $dependencyArguments = $attribute->getArguments()
                            };
                        }
                        $arguments = array_merge($arguments, array_map(function (string $resourceIdentifier) use ($bootstrap) {
                            return $bootstrap($resourceIdentifier);
                        }, $dependencyArguments));
                    }
                }
            } catch (ReflectionException $e) {
                trigger_error($e->getMessage());
            }

            return $resources($identifier)(...$arguments);
        };

        return $bootstrap;
    }

    public static function resourceRequiresConfigurationParameter(ReflectionParameter $firstParameter): bool
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
