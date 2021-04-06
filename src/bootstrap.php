<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap{
    function __open(string $resourcePath)
    {
        $resourcePath = str_replace("\\", chr(47), $resourcePath);
        static $closures = [];
        if (!isset($closures[$resourcePath])) {
            $closures[$resourcePath] = require substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $resourcePath;
        }
        return $closures[$resourcePath];
    }
}namespace rikmeijer\Bootstrap { 
    if (function_exists("rikmeijer\Bootstrap\configure") === false) {
        function configure(callable $function, array $schema, ?string $configSection = null)
        {
            return __open('/configure.php')(...func_get_args());
        }

    }
}namespace rikmeijer\Bootstrap { 
    if (function_exists("rikmeijer\Bootstrap\generate") === false) {
        function generate()
        {
            return __open('/generate.php')(...func_get_args());
        }

    }
}