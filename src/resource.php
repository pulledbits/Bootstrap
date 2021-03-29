<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\resource{
function __open(string $resourcePath) { $resourcePath = str_replace("\\", chr(47), $resourcePath); return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . substr($resourcePath, strrpos($resourcePath, chr(47))), false); }
}namespace rikmeijer\Bootstrap\resource { 
    if (function_exists("rikmeijer\Bootstrap\resource\generate") === false) {
    function generate(callable $resourceOpenerArgs, callable $fopen, string $resourcesPath, string $namespace): void
{
	\rikmeijer\Bootstrap\resource\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/resource\\generate.php')(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\resource { 
    if (function_exists("rikmeijer\Bootstrap\resource\open") === false) {
    function open(string $resourcePath, bool $cache): mixed
{
	static $closure; if (!isset($closure)) $closure = (include __DIR__ . DIRECTORY_SEPARATOR . "resource/open.php"); return $closure(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap\resource { 
    if (function_exists("rikmeijer\Bootstrap\resource\opener") === false) {
    function opener(callable $arguments): callable
{
	return \rikmeijer\Bootstrap\resource\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/resource\\opener.php')(...func_get_args());
}

    }
}