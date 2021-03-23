<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;
use function Functional\partial_left;
use function trigger_error;


class Configuration
{
    public static function open(): callable
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
        }, Bootstrap::configurationPath() . DIRECTORY_SEPARATOR . 'config.php');
    }

    public static function validate(array $schema, string $section): array
    {
        $configuration = self::open()($section);
        if (count($schema) === 0) {
            return $configuration;
        }
        $map = [];
        foreach ($schema as $key => $validator) {
            $error = static function (string $message) use ($key): void {
                trigger_error($key . ' ' . $message, E_USER_ERROR);
            };
            $map[$key] = $validator($configuration[$key] ?? null, $error);
        }
        return $map;
    }

    public static function default(mixed $defaultValue, mixed $value, callable $error): mixed
    {
        return $value ?? $defaultValue ?? $error('is not set and has no default value');
    }

    public static function pathValidator(?string $defaultValue): callable
    {
        return partial_left(static function (?string $defaultValue, mixed $value, callable $error) {
            if ($value === null) {
                $value = $defaultValue ?? $error('is not set and has no default value');
            }
            if (str_starts_with($value, 'php://')) {
                return $value;
            }
            if (Path::isRelative($value)) {
                return Path::join(Bootstrap::configurationPath(), $value);
            }
            return $value;
        }, $defaultValue);
    }

    public static function fileValidator(?string $defaultValue): callable
    {
        $pathValidator = self::pathValidator($defaultValue);
        return static function (mixed $value, callable $error) use ($pathValidator) {
            $path = $pathValidator($value, $error);
            return static function (string $mode) use ($path) {
                return fopen($path, $mode);
            };
        };
    }
}