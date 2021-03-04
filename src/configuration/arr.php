<?php declare(strict_types=1);

return static function (array $defaultValue): callable {
    /** @noinspection PhpUndefinedFunctionInspection */
    return rikmeijer\Bootstrap\configuration\mixed($defaultValue);
};