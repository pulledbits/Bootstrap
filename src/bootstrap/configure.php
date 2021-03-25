<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use function rikmeijer\Bootstrap\Configuration\path;

$schema = [
    'resources' => types\path(path() . DIRECTORY_SEPARATOR . 'bootstrap')
];

return F\partial_left(static function (array $configuration, callable $function, array $schema, ?string $configSection = null): callable {
    if ($configSection === null) {
        $configSection = (static function (int $resourcesInode, string $resourceDir) {
            $configSection = [];
            while ($resourceDir !== '') {
                $lastSlash = strrpos($resourceDir, DIRECTORY_SEPARATOR) ?: 0;
                array_unshift($configSection, substr($resourceDir, $lastSlash + 1));
                $resourceDir = substr($resourceDir, 0, $lastSlash);
                if (fileinode($resourceDir) === $resourcesInode) {
                    break;
                }
            }
            return implode('/', $configSection);
        })(fileinode($configuration['resources']), substr(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]["file"], 0, -4));
    }
    return F\partial_left($function, Configuration::validateSection($schema, $configSection));
}, Configuration::validateSection($schema, 'BOOTSTRAP'));