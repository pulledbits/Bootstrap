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
            $context = PHP::deductContextFromFile($resourcePath);

            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($resourceNSPath !== '') {
                $resourceNS = $resourcesNS . '\\' . $resourceNSPath;
            } else {
                $resourceNS = $resourcesNS;
            }

            $parameters = '';
            if (array_key_exists('parameters', $context)) {
                $parameters = $context['parameters'];
            }

            $returnType = '';
            if (array_key_exists('returnType', $context)) {
                $returnType = $context['returnType'];
            }

            $configCode = '\\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($resourceNSPath . basename($resourcePath, '.php')) . ', $schema);';
            fwrite($fp, PHP::function($resourceNS . '\\' . basename($resourcePath, '.php'), 'validate', 'array $schema', ': array', $configCode));
            fwrite($fp, PHP::function($resourceNS, basename($resourcePath, '.php'), $parameters, $returnType, '\\' . Resource::class . '::require(' . PHP::export($resourcePath) . ', static function(array $schema) {
                            return ' . $configCode . '
                        })(...func_get_args());'));
        });
        fclose($fp);
    }
}