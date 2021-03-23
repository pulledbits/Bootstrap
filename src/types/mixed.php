<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

use Functional as F;
use rikmeijer\Bootstrap\Configuration;

return static function (mixed $defaultValue): callable {
    return F\partial_left([Configuration::class, 'default'], $defaultValue);
};