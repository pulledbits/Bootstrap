<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use rikmeijer\Bootstrap\PHP;
use function Functional\partial_left;

return static function (callable $resourceOpenerArgs, callable $fopen, string $resourcesPath, string $namespace): void {
    $temp = fopen('php://memory', 'wb+');
    $writer = partial_left('\\fwrite', $temp);
    $writer('<?php declare(strict_types=1);' . PHP_EOL);
    $generator = partial_left(static function (callable $resourceOpener, callable $writer, string $path, string $namespace) use (&$generator, $resourcesPath): void {
        $openFunction = $resourceOpener($writer, $namespace);
        foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                $generator($path . DIRECTORY_SEPARATOR . basename($resourceFilePath), trim($namespace . '\\' . basename($resourceFilePath), '\\'));
            } elseif (str_ends_with($resourceFilePath, '.php')) {
                $writer(PHP::extractGlobalFunctionFromFile($resourcesPath, $path, basename($resourceFilePath, '.php'), $namespace, $openFunction));
            }
        }
    }, opener($resourceOpenerArgs), $writer);
    $generator('', $namespace);

    fseek($temp, 0);

    $fp = $fopen('wb');
    while (feof($temp) === false) {
        fwrite($fp, fread($temp, 1024));
    }
    fclose($temp);
    fclose($fp);
};