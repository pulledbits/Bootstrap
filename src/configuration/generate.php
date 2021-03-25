<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\Configuration;

use Functional as F;
use rikmeijer\Bootstrap\PHP;

function generate(string $from, string $namespace, bool $cached): void
{
    $fp = fopen($from . '.php', 'wb');
    $write = F\partial_left('\\fwrite', $fp);
    $write('<?php declare(strict_types=1);' . PHP_EOL);
    $write('namespace ' . $namespace . ';' . PHP_EOL);
    $write('function open(string $functionIdentifier) { return \\rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $functionIdentifier, ' . PHP::export($cached) . '); }' . PHP_EOL);
    \rikmeijer\Bootstrap\resource\generate($from, '', F\partial_left(static function (callable $write, string $namespace, string $resourcePath) {
        $write('if (function_exists("' . $namespace . '\\' . basename($resourcePath, '.php') . '") === false) {' . PHP_EOL);
        $write(PHP::extractGlobalFunctionFromFile($resourcePath)('open(' . PHP::export(basename($resourcePath)) . ')(...func_get_args());') . PHP_EOL);
        $write('}');
    }, $write, $namespace));
    fclose($fp);
}