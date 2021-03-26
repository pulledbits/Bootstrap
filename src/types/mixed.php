<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

use Functional as F;

return static function (mixed $defaultValue): callable {
    return F\partial_left(static function (mixed $defaultValue, mixed $value, callable $error): mixed {
        return $value ?? $defaultValue ?? $error('is not set and has no default value');
    }, $defaultValue);
};