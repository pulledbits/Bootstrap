<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use function Functional\map;
use function Functional\partial_left;
use function rikmeijer\Bootstrap\configuration\path;

$validateSection = static function (array $schema, string $section): array {
    /** @noinspection PhpIncludeInspection */
    $config = (include path() . DIRECTORY_SEPARATOR . 'config.php');
    return map($schema, partial_left('\rikmeijer\Bootstrap\configuration\validate', $config[$section] ?? []));
};

$schema = [
    'resources' => types\path(path() . DIRECTORY_SEPARATOR . 'bootstrap')
];

return F\partial_left(static function (array $configuration, callable $validateSection, callable $function, array $schema, ?string $configSection = null): callable {
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
    return F\partial_left($function, $validateSection($schema, $configSection));
}, $validateSection($schema, 'BOOTSTRAP'), $validateSection);