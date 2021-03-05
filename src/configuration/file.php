<?php declare(strict_types=1);

use Functional as F;
use rikmeijer\Bootstrap\Configuration;

return static function (string ...$defaultValue): callable {
    $pathValidator = F\partial_right(F\partial_left([Configuration::class, "path"], implode(DIRECTORY_SEPARATOR, $defaultValue)), ["configuration-path" => __DIR__]);
    return static function (mixed $value, callable $error, array $context) use ($pathValidator) {
        $path = $pathValidator($value, $error, $context);
        return static function (string $mode) use ($path) {
            return fopen($path, $mode);
        };
    };
};