<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;

/**
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param array $array2
 * @return array
 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
 */
function array_merge_recursive_distinct(array $array1, array $array2): array
{
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
            $merged [$key] = array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged [$key] = $value;
        }
    }

    return $merged;
}


final class Bootstrap
{
    public static function load(string $configurationPath): Closure
    {
        $path = function () use ($configurationPath): string {
            $configuration = Configuration::open($configurationPath, ('BOOTSTRAP'));
            if (array_key_exists('path', $configuration)) {
                return $configuration['path'];
            }
            return $configurationPath . DIRECTORY_SEPARATOR . 'bootstrap';
        };

        $resources = [];
        return $bootstrap = function (string $identifier) use (&$bootstrap, $configurationPath, $path, &$resources) {
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
                        $arguments[$firstParameter->getName()] = Configuration::open($configurationPath, ($identifier));
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

            }

            return $resources[$identifier] = $resource(...$arguments);
        };
    }
}
