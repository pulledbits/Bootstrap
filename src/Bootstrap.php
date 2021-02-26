<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function initialize(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        if (file_exists($config['functions-path'])) {
            require $config['functions-path'];
        }
    }

    public static function configuration(string $configurationPath): array
    {
        return Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'functions-path' => Configuration::path('_f.php'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\' . basename($configurationPath))]);
    }

    public static function generate(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        $resourcesNS = $config['namespace'];
        $fp = fopen($config['functions-path'], 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate($config['path'], '', static function (string $resourceNSPath, string $resourcePath) use ($configurationPath, $resourcesNS, $fp) {
            if (preg_match('/namespace (?<namespace>((\w+)\\\\?)+);/m', file_get_contents($resourcePath), $matches) === 1) {
                $resourceNS = $matches['namespace'];
            } elseif ($resourceNSPath !== '') {
                $resourceNS = $resourcesNS . '\\' . $resourceNSPath;
            } else {
                $resourceNS = $resourcesNS;
            }
            fwrite($fp, PHP::wrapResource($resourceNS, basename($resourcePath, '.php'), '\\' . Resource::class . '::require(' . PHP::export($resourcePath) . ', static function(array $schema) {
                            return \\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($resourceNSPath . basename($resourcePath, '.php')) . ', $schema);
                        });' . PHP_EOL));
        });
        fclose($fp);
    }
}