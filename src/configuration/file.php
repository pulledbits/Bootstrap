<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use rikmeijer\Bootstrap\Configuration;

return static function (?string ...$defaultValue): callable {
    return Configuration::fileValidator(...$defaultValue);
};