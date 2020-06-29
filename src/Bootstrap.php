<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;
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
function array_merge_recursive_distinct ( array $array1, array $array2 ) : array
{
    $merged = $array1;

    foreach ( $array2 as $key => &$value )
    {
        if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
        {
            $merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
        }
        else
        {
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
    }

    public function resource(string $resource) : object
    {
        if (array_key_exists($resource, $this->resources)) {
            return $this->resources[$resource];
        }
        $path = $this->config('BOOTSTRAP')['path'];
        return $this->resources[$resource] = (require $path . DIRECTORY_SEPARATOR . $resource . '.php')($this, $this->config($resource));
    }

    public function config(string $section): array
    {
        if (isset($this->config) === false) {
            $this->config = (include $this->configurationPath . DIRECTORY_SEPARATOR . 'config.defaults.php');
            if (file_exists($this->configurationPath . DIRECTORY_SEPARATOR . 'config.php')) {
                $this->config = array_merge_recursive_distinct($this->config, (include $this->configurationPath . DIRECTORY_SEPARATOR . 'config.php'));
            }
        }
        if (array_key_exists($section, $this->config) === false) {
            return [];
        }
        return $this->config[$section];
    }
}
