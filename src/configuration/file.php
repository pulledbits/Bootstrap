<?php declare(strict_types=1);

use rikmeijer\Bootstrap\Configuration;

return static function (string ...$defaultValue): callable {
    return Configuration::fileValidator(...$defaultValue);
};