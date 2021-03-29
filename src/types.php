<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\types{

    use function rikmeijer\Bootstrap\resource\open;

    function __open(string $resourcePath)
    {
        $resourcePath = str_replace("\\", chr(47), $resourcePath);
        return open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . substr($resourcePath, strrpos($resourcePath, chr(47))), false);
    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\arr") === false) {
    function arr(?array $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\arr.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\binary") === false) {
    function binary(array $defaultCommand = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\binary.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\boolean") === false) {
    function boolean(?bool $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\boolean.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\file") === false) {
    function file(?string $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\file.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\float") === false) {
    function float(?float $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\float.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\integer") === false) {
    function integer(?int $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\integer.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\mixed") === false) {
    function mixed(mixed $defaultValue): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\mixed.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\path") === false) {
    function path(?string $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\path.php')(...func_get_args());
    }

    }
}namespace rikmeijer\Bootstrap\types { 
    if (function_exists("rikmeijer\Bootstrap\types\string") === false) {
    function string(?string $defaultValue = null): callable
    {
        return __open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/types\\string.php')(...func_get_args());
    }

    }
}