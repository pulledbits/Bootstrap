<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

use function Functional\partial_left;

function generate(string $resourcesPath): callable
{
    $recursor = partial_left(static function (string $baseDirectory, string $namespace, string $path, callable $writer) use (&$recursor): void {
        foreach (glob($baseDirectory . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '') . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
            if (is_dir($resourceFilePath)) {
                $recursor(trim($namespace . '\\' . basename($resourceFilePath), '\\'), trim($path . '/' . basename($resourceFilePath), '/'), $writer);
            } elseif (str_ends_with($resourceFilePath, '.php')) {
                $writer($resourceFilePath, $namespace);
            }
        }
    }, $resourcesPath);
    return partial_left($recursor, '');
}