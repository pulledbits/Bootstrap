<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use function Functional\map;
use function Functional\partial_left;
use function rikmeijer\Bootstrap\Configuration\path;
use function trigger_error;


class Configuration
{
    public static function validateSection(array $schema, string $section): array
    {
        /** @noinspection PhpIncludeInspection */
        $config = (include path() . DIRECTORY_SEPARATOR . 'config.php');
        return map($schema, partial_left([__CLASS__, 'validate'], $config[$section] ?? []));
    }

    public static function validate(array $configuration, callable $validator, string $property): mixed
    {
        return $validator($configuration[$property] ?? null, partial_left([__CLASS__, 'error'], $property));
    }

    public static function error(string $property, string $message): void
    {
        trigger_error($property . ' ' . $message, E_USER_ERROR);
    }

    public static function default(mixed $defaultValue, mixed $value, callable $error): mixed
    {
        return $value ?? $defaultValue ?? $error('is not set and has no default value');
    }
}