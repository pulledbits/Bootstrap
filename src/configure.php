<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;

function configure(callable $function, array $schema, ?string $configSection = null): callable
{
    if ($configSection === null) {
        $configSection = (static function (string $resourcePath) {
            $resources = F\partial_left('Functional\\head', resources());
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
            return implode('/', $configSection);
        })(substr(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]["file"], 0, -4));
    }
    return F\partial_left($function, Configuration::validate($schema, $configSection));
}