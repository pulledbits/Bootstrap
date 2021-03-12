<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use rikmeijer\Bootstrap\Configuration;

return static function (?string $defaultValue = null): callable {
    return Configuration::pathValidator($defaultValue);
};