<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

function file(?string $defaultValue = null): callable
{
    $pathValidator = path($defaultValue);
    return static function (mixed $value, callable $error) use ($pathValidator) {
        $path = $pathValidator($value, $error);
        return static function (string $mode) use ($path) {
            return fopen($path, $mode);
        };
    };
}