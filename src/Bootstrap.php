<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function initialize(string $configurationPath): callable
    {
        $config = self::configuration($configurationPath);
        if (file_exists($config['functions-path'])) {
            require_once $config['functions-path'];
        }
        return Resource::loader($configurationPath);
    }


    public static function configuration(string $configurationPath): array
    {
        return Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'functions-path' => Configuration::path('_f.php'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\f\\' . basename($configurationPath))]);
    }

    public static function generate(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        file_put_contents($config['functions-path'], '<?php' . PHP_EOL);
        Resource::generate($config['path'], $configurationPath);
    }
}