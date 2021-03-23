<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

return static function (?bool $defaultValue = null): callable {
    return mixed($defaultValue);
};