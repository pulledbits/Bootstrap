<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;
use function Functional\partial_left;
use function trigger_error;


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
        $map = [];
        foreach ($schema as $key => $validator) {
            $error = static function (string $message) use ($key) {
                trigger_error($key . ' ' . $message, E_USER_ERROR);
            };
            $map[$key] = $validator($configuration[$key] ?? null, $error, $context);
        }
        return $map;
    }

    public static function default(mixed $defaultValue, mixed $value, callable $error): mixed
    {
        return $value ?? $defaultValue ?? $error('is not set and has no default value');
    }

    public static function path(string $defaultValue, mixed $value, callable $error, array $context): mixed
    {
        if ($value === null) {
            $value = $defaultValue ?? $error('is not set and has no default value');
        }
        if (Path::isRelative($value)) {
            return Path::join($context['configuration-path'], $value);
        }
        return $value;
    }
}