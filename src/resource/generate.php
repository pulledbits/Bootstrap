<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\resource;

function generate(string $resourcesPath, string $namespace, callable $writer): void
{
    foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . '*') as $resourceFilePath) {
        if (is_dir($resourceFilePath)) {
            generate($resourceFilePath, trim($namespace . '\\' . basename($resourceFilePath), '\\'), $writer);
        } elseif (str_ends_with($resourceFilePath, '.php')) {
            $writer($resourceFilePath, $namespace);
        }
    }
}