<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;

function configure(callable $function, array $schema): callable
{
    $resources = F\partial_left('Functional\\head', array_merge(Bootstrap::resources(), [__DIR__ => 'types']));
    $resourcePath = substr(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]["file"], 0, -4);
    $resourceDir = preg_split('#[/\\\\]+#', $resourcePath);
    $configSection = [];

    do {
        array_unshift($configSection, array_pop($resourceDir));
        $path = $resources(F\partial_left(static function (string $resourceDir, string $path, string $resourcesPath): bool {
            return fileinode($resourceDir) === fileinode($resourcesPath . DIRECTORY_SEPARATOR . $path);
        }, implode(DIRECTORY_SEPARATOR, $resourceDir)));
    } while ($path === null);
    if ($path !== '') {
        array_unshift($configSection, $path);
    }
    return F\partial_left($function, Configuration::validate($schema, implode('/', $configSection)));
}