<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

use rikmeijer\Bootstrap\Configuration;

return static function (?string $defaultValue = null): callable {
    return Configuration::fileValidator($defaultValue);
};