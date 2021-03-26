<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

return static function (string $resourcePath, bool $cache): mixed {
    static $closures = [];
    if (!isset($closures[$resourcePath]) || $cache === false) {
        $closures[$resourcePath] = require $resourcePath;
    }
    return $closures[$resourcePath];
};