<?php declare(strict_types=1);

return static function (float $defaultValue): callable {
    /** @noinspection PhpUndefinedFunctionInspection */
    return rikmeijer\Bootstrap\configuration\mixed($defaultValue);
};