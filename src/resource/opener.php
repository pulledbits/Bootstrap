<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use function Functional\partial_left;

function opener(callable $arguments): callable
{
    return partial_left(static function (callable $arguments, callable $write, string $namespace) {
        $write('namespace ' . $namespace . '{' . PHP_EOL);
        $write('function open(string $resourcePath) { return \\rikmeijer\Bootstrap\resource\\open(' . $arguments() . '); }' . PHP_EOL);
        $write('}');
    }, $arguments);
}