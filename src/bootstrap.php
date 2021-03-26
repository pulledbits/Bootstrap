<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap{
function __open(string $resourcePath) { return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . basename($resourcePath), false); }
}namespace rikmeijer\Bootstrap { 
    if (function_exists("rikmeijer\Bootstrap\configure") === false) {
    function configure()
{
	return \rikmeijer\Bootstrap\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/bootstrap\\configure.php')(...func_get_args());
}

    }
}namespace rikmeijer\Bootstrap { 
    if (function_exists("rikmeijer\Bootstrap\generate") === false) {
    function generate(): void
{
	\rikmeijer\Bootstrap\__open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/bootstrap\\generate.php')(...func_get_args());
}

    }
}