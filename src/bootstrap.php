<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap{
function open(string $resourcePath) { return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . basename($resourcePath), false); }
}namespace rikmeijer\Bootstrap { 
    
        if (function_exists("rikmeijer\Bootstrap\apply") === false) {
    function _apply(): callable
{
	return \rikmeijer\Bootstrap\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/bootstrap\\apply.php');
}

    function apply()
{
	return (_apply())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap { 
    
        if (function_exists("rikmeijer\Bootstrap\configure") === false) {
    function _configure(): callable
{
	return \rikmeijer\Bootstrap\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/bootstrap\\configure.php');
}

    function configure(callable $unconfiguredFunction, array $schema, ?string $configSection = null): callable
{
	return (_configure())(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap { 
    
        if (function_exists("rikmeijer\Bootstrap\generate") === false) {
    function _generate(): callable
{
	return \rikmeijer\Bootstrap\open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/bootstrap\\generate.php');
}

    function generate(): void
{
	(_generate())(...func_get_args());
}

    }
}