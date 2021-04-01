<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\configuration{

    use function rikmeijer\Bootstrap\resource\open;

    function __open(string $resourcePath)
    {
        $resourcePath = str_replace("\\", chr(47), $resourcePath);
        return open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $resourcePath, false);
    }
}namespace rikmeijer\Bootstrap\configuration { 
    if (function_exists("rikmeijer\Bootstrap\configuration\generate") === false) {
        function generate(string $from, string $namespace, bool $cached): void
        {
            __open('/generate.php')(...func_get_args());
        }

    }
}namespace rikmeijer\Bootstrap\configuration { 
    if (function_exists("rikmeijer\Bootstrap\configuration\path") === false) {
        function path(): string
        {
            return __open('/path.php')(...func_get_args());
        }

    }
}namespace rikmeijer\Bootstrap\configuration { 
    if (function_exists("rikmeijer\Bootstrap\configuration\validate") === false) {
        function validate(array $config, callable $validator, string $property): mixed
        {
            return __open('/validate.php')(...func_get_args());
        }

    }
}