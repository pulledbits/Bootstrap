<?php declare(strict_types=1);

return static function (string $defaultValue): callable {
    /** @noinspection PhpUndefinedFunctionInspection */
    return rikmeijer\Bootstrap\configuration\mixed($defaultValue);
};