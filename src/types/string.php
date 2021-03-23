<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

return static function (?string $defaultValue = null): callable {
    return mixed($defaultValue);
};