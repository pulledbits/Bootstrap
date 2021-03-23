<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\Configuration;

use Functional as F;
use rikmeijer\Bootstrap\PHP;
use rikmeijer\Bootstrap\Resource;

function generate(): void
{
    $fp = fopen(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'types.php', 'wb');
    $write = F\partial_left('\\fwrite', $fp);
    $write('<?php declare(strict_types=1);' . PHP_EOL);
    $write('namespace rikmeijer\Bootstrap\types;' . PHP_EOL);
    $write('use \\' . Resource::class . ';' . PHP_EOL);
    $write('function open(string $functionIdentifier) { return Resource::open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $functionIdentifier, false); }' . PHP_EOL);
    Resource::generate([dirname(__DIR__) => 'types'], F\partial_left(static function (callable $write, string $resourcePath) {
        $write('if (function_exists("\\rikmeijer\\Bootstrap\\types\\' . basename($resourcePath, '.php') . '") === false) {' . PHP_EOL);
        $write(PHP::extractGlobalFunctionFromFile($resourcePath)('open(' . PHP::export(basename($resourcePath)) . ')(...func_get_args());') . PHP_EOL);
        $write('}');
    }, $write));
    fclose($fp);
}