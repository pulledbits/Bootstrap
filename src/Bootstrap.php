<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Closure;

final class Bootstrap
{
    public static function load(string $configurationPath): callable
    {
        $config = static function (string $section, array $schema) use ($configurationPath) {
            return Configuration::open($configurationPath, $section, $schema);
        };

        $path = static function (string $identifier) use ($config): string {
            $configuration = $config('BOOTSTRAP', ['path' => ['url', ['default' => '%configuration-path%' . DIRECTORY_SEPARATOR . 'bootstrap']]]);
            return $configuration['path'] . DIRECTORY_SEPARATOR . $identifier . '.php';
        };

        $resourcesCache = [];
        $resources = static function (string $identifier) use ($config, $path, &$resourcesCache): callable {
            if (array_key_exists($identifier, $resourcesCache)) {
                return $resourcesCache[$identifier];
            }
            return $resourcesCache[$identifier] = self::require($path($identifier), $config($identifier, []));
        };

        return new class($resources) { // use class since PHP does not natively support a closure calling itself
            public function __construct(private Closure $resources)
            {
            }

            public function __invoke(string $identifier): callable
            {
                $resource = ($this->resources)($identifier);
                return $resource(...Resource::arguments($this, $resource));
            }
        };
    }

    /** @noinspection PhpIncludeInspection PhpUnusedParameterInspection */
    private static function require(string $path, array $configuration): callable
    {
        return require $path;
    }
}
