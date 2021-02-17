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
 * @param array ...$arrays
 * @return array
 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
 * @author Rik Meijer <hello (at) rikmeijer (dot) nl>
 */
function array_merge_recursive_distinct(array $array1, array ...$arrays): array
{
    $merge = static function (array $merged, array ...$arrays) use (&$merge): array {
        array_walk($arrays, static function (array $array) use ($merge, &$merged) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                    $merged [$key] = $merge($merged[$key], $value);
                } else {
                    $merged [$key] = $value;
                }
            }
        });
        return $merged;
    };
    return $merge($array1, ...$arrays);
}

function array_map_assoc(callable $callback, array $array, array ...$arrays): array
{
    $map = [];
    foreach ($array as $key => $value) {
        $map[$key] = $callback($value, ...(static function (string|int $key, array $arrays): array {
            return array_map(static function (array $array) use ($key): mixed {
                return $array[$key] ?? null;
            }, $arrays);
        })($key, $arrays));
    }
    return $map;
}

class Configuration
{
    private static array $configs = [];

    public static function open(string $root, string $section, array $schema): array
    {
        if (array_key_exists($root, self::$configs) === false) {
            self::$configs[$root] = array_merge_recursive_distinct([$section => []], self::openDefaults($root), self::openLocal($root));
        }
        if (array_key_exists($section, self::$configs[$root]) === false) {
            self::$configs[$root][$section] = [];
        }
        return self::validate($schema, self::$configs[$root][$section], ['configuration-path' => $root]);
    }

    private static function openDefaults(string $root): array
    {
        return self::include($root . DIRECTORY_SEPARATOR . 'config.defaults.php');
    }

    private static function include(string $path): array
    {
        if (file_exists($path) === false) {
            return [];
        }
        $config = (include $path);
        return is_array($config) ? $config : [];
    }

    private static function openLocal(string $root): array
    {
        return self::include($root . DIRECTORY_SEPARATOR . 'config.php');
    }

    private static function validate(array $schema, array $configuration, array $context): array
    {
        if (count($schema) === 0) {
            return $configuration;
        }
        return array_map_assoc(function (callable $validator, mixed $value) use ($context) {
            return $validator($value, $context);
        }, $schema, $configuration);
    }

    public static function default(mixed $defaultValue): callable
    {
        return static function (mixed $value) use ($defaultValue): mixed {
            return $value ?? $defaultValue;
        };
    }

    public static function path(string $defaultValue): callable
    {
        return static function (mixed $value, array $context) use ($defaultValue): mixed {
            return $value ?? ($context['configuration-path'] . DIRECTORY_SEPARATOR . $defaultValue);
        };
    }
}