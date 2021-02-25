<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function initialize(string $configurationPath): callable
    {
        $configuration = Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\f\\' . basename($configurationPath))]);
        return Resource::loader($configurationPath, $configuration['path'], $configuration['namespace']);
    }

}
