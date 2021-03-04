<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

return static function (mixed $defaultValue, mixed $value): mixed {
    return $value ?? $defaultValue;
};