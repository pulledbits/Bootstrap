<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap{

    use function rikmeijer\Bootstrap\resource\open;

    function __open(string $resourcePath)
    {
        $resourcePath = str_replace("\\", chr(47), $resourcePath);
        return open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $resourcePath, false);
    }
}namespace rikmeijer\Bootstrap { 
    if (function_exists("rikmeijer\Bootstrap\configure") === false) {
        function configure(callable $function, array $schema, ?string $configSection = null): callable
        {
            return __open('/configure.php')(...func_get_args());
        }

    }
}namespace rikmeijer\Bootstrap { 
    if (function_exists("rikmeijer\Bootstrap\generate") === false) {
        function generate(): void
        {
            __open('/generate.php')(...func_get_args());
        }

    }
}