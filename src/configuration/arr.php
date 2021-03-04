<?php declare(strict_types=1);

use Functional as F;

return static function (array $defaultValue): callable {
    return F\partial_left('rikmeijer\Bootstrap\configuration\mixed', $defaultValue);
};