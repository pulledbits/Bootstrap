<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Closure;
use ReflectionFunction;

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
    private array $config;

    public function __construct(string $configurationPath)
    {
        $this->configurationPath = $configurationPath;
        $this->configuration = $this->config('BOOTSTRAP');
    }

    public function resource(string $identifier): object
    {
        if (array_key_exists($identifier, $this->resources)) {
            return $this->resources[$identifier];
        }

        $resource = $this->openResource($identifier);

        $reflection = new ReflectionFunction($resource);

        $arguments = [];
        if ($reflection->getNumberOfParameters() > 0) {
            $arguments[] = $this->config($identifier);
            if ($reflection->getNumberOfParameters() > 1) { // multiple parameters
                $resourceNS = $this->configuration['resource-namespace'];
                foreach (array_slice($reflection->getParameters(), 1) as $reflectionParameter) {
                    $type = $reflectionParameter->getType();
                    $class = null !== $type ? $type->getName() : null;


                    spl_autoload_register($autoloader = function (string $class) use ($resourceNS) {
                        if (strpos($class, $resourceNS) === 0) {
                            $resource = $this->openResource(str_replace([$resourceNS . '\\', '\\'], ['', '/'], $class));
                            $returnType = (new ReflectionFunction($resource))->getReturnType();
                            $positionLastNSSeparator = strrpos($class, '\\');
                            $namespace = substr($class, 0, $positionLastNSSeparator);
                            eval('namespace ' . $namespace . ' { 
                        class ' . substr($class, $positionLastNSSeparator + 1) . ' {
                            public function __construct(private \\' . __CLASS__ . ' $bootstrap) {}
                            public function __invoke() : ' . (is_null($returnType) === false ? $returnType : 'void') . ' {
                                 ' . (is_null($returnType) === false ? 'return ' : '') . '$this->bootstrap->resource("' . str_replace([$resourceNS . '\\', '\\'], ['', '/'], $class) . '");
                            }
                        } 
                    }');
                        }
                    });

                    $arguments[] = new $class($this);

                    spl_autoload_unregister($autoloader);
                }
            }
        }
        return $this->resources[$identifier] = $resource(...$arguments);
    }

    private function openResource(string $identifier): Closure
    {
        if (array_key_exists('path', $this->configuration)) {
            $path = $this->configuration['path'];
        } else {
            $path = $this->configurationPath . DIRECTORY_SEPARATOR . 'bootstrap';
        }

        return (require $path . DIRECTORY_SEPARATOR . $identifier . '.php')->bindTo(new class($this) {
            private Bootstrap $bootstrap;

            public function __construct(Bootstrap $bootstrap)
            {
                $this->bootstrap = $bootstrap;
            }

            final public function resource(string $resource): object
            {
                return $this->bootstrap->resource($resource);
            }
        });
    }

    private function config(string $section): array
    {
        if (isset($this->config) === false) {
            $this->config = array_merge_recursive_distinct($this->openConfigDefaults(), $this->openConfigLocal());
        }
        if (array_key_exists($section, $this->config) === false) {
            return [];
        }
        return $this->config[$section];
    }

    private function openConfigDefaults(): array
    {
        return $this->openConfig($this->configurationPath . DIRECTORY_SEPARATOR . 'config.defaults.php');
    }

    private function openConfigLocal(): array
    {
        return $this->openConfig($this->configurationPath . DIRECTORY_SEPARATOR . 'config.php');
    }

    private function openConfig(string $path): array
    {
        if (file_exists($path) === false) {
            return [];
        }
        $config = (include $path);
        return is_array($config) ? $config : [];
    }
}
