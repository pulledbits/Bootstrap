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

        $path = static function () use ($config): string {
            $configuration = $config('BOOTSTRAP', ['path' => ['url', ['default' => '%configuration-path%' . DIRECTORY_SEPARATOR . 'bootstrap']]]);
            return $configuration['path'];
        };

        $resources = [];
        $bootstrap = static function (string $identifier) use (&$bootstrap, $configurationPath, $path, &$resources) {
            if (array_key_exists($identifier, $resources)) {
                return $resources[$identifier];
            }
            $resourcePath = static function (Closure $path, string $identifier) {
                return $path() . DIRECTORY_SEPARATOR . $identifier . '.php';
            };

            $resourceRequiresConfigurationParameter = function (ReflectionParameter $firstParameter): bool {
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
            };

            $resource = require $resourcePath($path, $identifier);

            $arguments = [];
            try {
                $reflection = new ReflectionFunction($resource);
                if ($reflection->getNumberOfParameters() > 0) {
                    $firstParameter = $reflection->getParameters()[0];
                    if ($resourceRequiresConfigurationParameter($firstParameter)) {
                        $arguments[$firstParameter->getName()] = Configuration::open($configurationPath, $identifier, []);
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

            return $resources[$identifier] = $resource(...$arguments);
        };

        return $bootstrap;
    }
}
