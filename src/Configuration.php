<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use function rikmeijer\Bootstrap\Configuration\path;
use function trigger_error;


class Configuration
{
    public static function validateSection(array $schema, string $section): array
    {
        /** @noinspection PhpIncludeInspection */
        $config = (include path() . DIRECTORY_SEPARATOR . 'config.php');
        if (is_array($config) === false) {
            $configuration = [];
        } elseif (array_key_exists($section, $config) === false) {
            $configuration = [];
        } elseif (is_array($config[$section]) === false) {
            $configuration = [];
        } else {
            $configuration = $config[$section];
        }

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
}