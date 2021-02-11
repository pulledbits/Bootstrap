<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function load(string $configurationPath): callable
    {
        return Resource::wrap(static function (string $section, array $schema) use ($configurationPath) {
            return Configuration::open($configurationPath, $section, $schema);
        });
    }

    public static function resourcesPath(callable $config): string
    {
        $configuration = $config('BOOTSTRAP', ['path' => ['url', ['default' => '%configuration-path%' . DIRECTORY_SEPARATOR . 'bootstrap']]]);
        return $configuration['path'];
    }
}
