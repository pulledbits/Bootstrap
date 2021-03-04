<?php declare(strict_types=1);

use Functional as F;
use rikmeijer\Bootstrap\Configuration;

return static function (string ...$defaultValue): callable {
    return F\partial_right(F\partial_left([Configuration::class, "path"], implode(DIRECTORY_SEPARATOR, $defaultValue)), ["configuration-path" => __DIR__]);
};