<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use rikmeijer\Bootstrap\PHP;
use function Functional\partial_left;

function generate(callable $resourceOpenerArgs, $fp, string $resourcesPath, string $namespace): void
{
    $temp = fopen('php://memory', 'wb+');
    $writer = partial_left('\\fwrite', $temp);
    $writer('<?php declare(strict_types=1);' . PHP_EOL);
    $generator = partial_left(static function (callable $resourceOpener, callable $writer, string $resourcesPath, string $namespace) use (&$generator): void {
        $resourceOpener($writer, $namespace);
        foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                $generator($resourceFilePath, trim($namespace . '\\' . basename($resourceFilePath), '\\'));
            } elseif (str_ends_with($resourceFilePath, '.php')) {
                $writer(PHP::extractGlobalFunctionFromFile($resourceFilePath, $namespace));
            }
        }
    }, opener($resourceOpenerArgs), $writer);
    $generator($resourcesPath, $namespace);

    fseek($temp, 0);
    while (feof($temp) === false) {
        fwrite($fp, fread($temp, 1024));
    }
    fclose($temp);
}