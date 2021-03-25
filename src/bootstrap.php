<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

function open(string $functionIdentifier)
{
    return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $functionIdentifier, false);
}

if (function_exists("rikmeijer\Bootstrap\configure") === false) {
    function configure(callable $function, array $schema, ?string $configSection = null): callable
    {
        return open('configure.php')(...func_get_args());
    }

}
if (function_exists("rikmeijer\Bootstrap\generate") === false) {
    function generate(): void
    {
        open('generate.php')(...func_get_args());
    }

}