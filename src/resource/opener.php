<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

//if (function_exists('rikmeijer\\Bootstrap\\resource\\' . basename(__FILE__, '.php')) === false) {
//    function opener() {
//        return (include __FILE__)(...func_get_args());
//    }
//}

use function Functional\partial_left;

return static function (callable $arguments): callable {
    return partial_left(static function (callable $arguments, callable $write, string $namespace): string {
        $write('namespace ' . $namespace . '{' . PHP_EOL);
        $write('function __open(string $resourcePath) { return \\rikmeijer\Bootstrap\resource\\open(' . $arguments('$resourcePath') . '); }' . PHP_EOL);
        $write('}');
        return $namespace . '\\__open';
    }, $arguments);
};