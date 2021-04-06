<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use function Functional\partial_left;

function opener(): callable
{
    return partial_left(static function (callable $write, string $namespace): string {
        $write('namespace ' . $namespace . '{' . PHP_EOL);
        $write('    function __open(string $resourcePath) {' . PHP_EOL);
        $write('        $resourcePath = str_replace("\\\\", chr(47), $resourcePath);' . PHP_EOL);
        $write('        static $closures = [];' . PHP_EOL);
        $write('        if (!isset($closures[$resourcePath])) {' . PHP_EOL);
        $write('             $closures[$resourcePath] = require substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $resourcePath;' . PHP_EOL);
        $write('        }' . PHP_EOL);
        $write('        return $closures[$resourcePath];' . PHP_EOL);
        $write('    }' . PHP_EOL);
        $write('}');
        return $namespace . '\\__open';
    });
}