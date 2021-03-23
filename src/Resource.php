<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

class Resource
{
    private static array $closures = [];

    public static function open(string $resourcePath, bool $cache): mixed
    {
        if (!isset(self::$closures[$resourcePath]) || $cache === false) {
            self::$closures[$resourcePath] = require $resourcePath;
        }
        return self::$closures[$resourcePath];
    }
}