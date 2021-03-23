<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

return static function (?float $defaultValue = null): callable {
    return mixed($defaultValue);
};