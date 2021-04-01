<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use function Functional\partial_left;

return static function (bool $cached): callable {
    return partial_left(static function (bool $cached, callable $write, string $namespace): string {
        $write('namespace ' . $namespace . '{' . PHP_EOL);
        $write('function __open(string $resourcePath) { $resourcePath = str_replace("\\\\", chr(47), $resourcePath); return \\rikmeijer\Bootstrap\resource\\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $resourcePath, ' . var_export($cached, true) . '); }' . PHP_EOL);
        $write('}');
        return $namespace . '\\__open';
    }, $cached);
};