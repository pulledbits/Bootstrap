<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

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
    private string $configurationPath;
    private array $resources = [];
    private string $path;

    public function __construct(string $configurationPath)
    {
        $this->configurationPath = $configurationPath;
        $this->path = $this->configurationPath . DIRECTORY_SEPARATOR . 'bootstrap';

        $configuration = Configuration::open($this->configurationPath, ('BOOTSTRAP'));
        if (array_key_exists('path', $configuration)) {
            $this->path = $configuration['path'];
        }
    }

    public static function load(string $configurationPath): self
    {
        return new self($configurationPath);
    }

    public function resource(string $identifier): object
    {
        if (array_key_exists($identifier, $this->resources)) {
            return $this->resources[$identifier];
        }

        $resource = (require $this->resourcePath($identifier))->bindTo(new class($this) {
            private Bootstrap $bootstrap;

            public function __construct(Bootstrap $bootstrap)
            {
                $this->bootstrap = $bootstrap;
            }

            /** @deprecated use DependencyAttribute instead */
            final public function resource(string $resource): object
            {
                return $this->bootstrap->resource($resource);
            }
        });

        $arguments = [];
        try {
            $reflection = new ReflectionFunction($resource);
            if ($reflection->getNumberOfParameters() > 0) {
                $firstParameter = $reflection->getParameters()[0];
                if ($this->resourceRequiresConfigurationParameter($firstParameter)) {
                    $arguments[$firstParameter->getName()] = Configuration::open($this->configurationPath, ($identifier));
                }

                if ($reflection->getNumberOfParameters() > count($arguments)) { // multiple parameters
                    $attributes = $reflection->getAttributes();
                    $dependencyArguments = [];
                    foreach ($attributes as $attribute) {
                        match ($attribute->getName()) {
                            Dependency::class => $dependencyArguments = $attribute->getArguments()
                        };
                    }
                    $arguments = array_merge($arguments, array_map(function (string $resourceIdentifier) {
                        return $this->resource($resourceIdentifier);
                    }, $dependencyArguments));
                }
            }
        } catch (ReflectionException $e) {

        }

        return $this->resources[$identifier] = $resource(...$arguments);
    }

    private function resourceRequiresConfigurationParameter(ReflectionParameter $firstParameter): bool
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

    private function resourcePath(string $identifier): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $identifier . '.php';
    }
}
