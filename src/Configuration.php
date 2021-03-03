<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;
use function Functional\partial_left;

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
    public static function open(string $root): callable
    {
        return partial_left(static function (string $path, string $section) {
            static $config;
            if (isset($config) === false) {
                if (file_exists($path) === false) {
                    $config = [];
                } else {
                    /** @noinspection PhpIncludeInspection */
                    $config = (include $path);
                    if (!is_array($config)) {
                        $config = [];
                    }
                }
            }
            if (array_key_exists($section, $config) === false) {
                return [];
            }
            return is_array($config[$section]) ? $config[$section] : [];
        }, $root . DIRECTORY_SEPARATOR . 'config.php');
    }

    public static function validate(array $schema, array $configuration, array $context): array
    {
        if (count($schema) === 0) {
            return $configuration;
        }
        return array_map_assoc(function (callable $validator, mixed $value) use ($context) {
            return $validator($value, $context);
        }, $schema, $configuration);
    }

    public static function default(mixed $defaultValue, mixed $value): mixed
    {
        return $value ?? $defaultValue;
    }

    public static function path(string $defaultValue, mixed $value, array $context): mixed
    {
        if ($value === null) {
            $value = $defaultValue;
        }
        if (Path::isRelative($value)) {
            return Path::join($context['configuration-path'], $value);
        }
        return $value;
    }
}