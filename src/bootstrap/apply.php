<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use ReflectionFunction;
use function Functional\partial_left;

if (function_exists(__NAMESPACE__ . '\\register') === false) {
    function register(callable $function, ?array $schema = null): ?array
    {
        static $schemas = [];
        $reflection = new ReflectionFunction($function);
        $filename = $reflection->getFileName();
        if ($schema !== null) {
            $schemas[$filename] = $schema;
        } elseif (array_key_exists($filename, $schemas)) {
            return $schemas[$filename];
        }
        return null;
    }
}

return static function (callable $function, array $configuration): callable {
    $reflection = new ReflectionFunction($function);
    $closure = ('\\' . $reflection->getNamespaceName() . '\\_' . $reflection->getShortName())();
    $closureStaticVariables = (new ReflectionFunction($closure))->getStaticVariables();
    if (array_key_exists('callback', $closureStaticVariables) === false) {
        return $function;
    }
    return partial_left($closureStaticVariables['callback'], Configuration::validate(register($closureStaticVariables['callback']), $configuration));
};