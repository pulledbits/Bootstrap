<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function initialize(string $configurationPath): callable
    {
        return Resource::loader($configurationPath);
    }


    public static function configuration(string $configurationPath): array
    {
        return Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\f\\' . basename($configurationPath))]);
    }
}
