<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    /** @noinspection PhpIncludeInspection */
    public static function initialize(string $configurationPath): void
    {
        require $configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.f.php';
    }

    public static function generate(string $configurationPath): void
    {
        $config = Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\' . basename($configurationPath))]);
        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.f.php', 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate($config['path'], '', static function (string $resourceNSPath, string $resourcePath) use ($config, $fp) {
            $context = PHP::deductContextFromFile($resourcePath);

            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($resourceNSPath !== '') {
                $resourceNS = $config['namespace'] . '\\' . $resourceNSPath;
            } else {
                $resourceNS = $config['namespace'];
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