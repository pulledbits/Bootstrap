<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use JetBrains\PhpStorm\NoReturn;
use function Functional\partial_left;
use function trigger_error;


class Configuration
{
    public static function validate(array $configuration, callable $validator, string $property): mixed
    {
        return $validator($configuration[$property] ?? null, partial_left([__CLASS__, 'error'], $property));
    }

    #[NoReturn]
    public static function error(string $property, string $message): void
    {
        trigger_error($property . ' ' . $message, E_USER_ERROR);
    }
}