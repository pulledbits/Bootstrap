<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function initialize(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        if (file_exists($configurationPath . DIRECTORY_SEPARATOR . $config['functions-filename'])) {
            require $configurationPath . DIRECTORY_SEPARATOR . $config['functions-filename'];
        }
    }

    public static function configuration(string $configurationPath): array
    {
        return Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'functions-filename' => Configuration::default('_f.php'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\' . basename($configurationPath))]);
    }

    public static function generate(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        $resourcesNS = $config['namespace'];
        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . $config['functions-filename'], 'wb');
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
            $void = false;
            if (array_key_exists('returnType', $context)) {
                $returnType = $context['returnType'];
                $void = str_contains($returnType, 'void') === false;
            }

            $identifier = basename($resourcePath, '.php');
            fwrite($fp, PHP::function($resourceNS . '\\' . $identifier, 'validate', 'array $schema', ': array', 'return \\' . Configuration::class . '::open(__DIR__, ' . PHP::export($resourceNSPath . $identifier) . ', $schema);'));
            fwrite($fp, PHP::function($resourceNS, $identifier, $parameters, $returnType, 'static $closure; if (!isset($closure)) { $closure = require ' . PHP::export($resourcePath) . '; }' . ($void === true ? '' : 'return ') . ' $closure(...func_get_args());'));
        });
        fclose($fp);
    }
}