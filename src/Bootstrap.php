<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function generate(string $configurationPath): void
    {
        $schema = ['path' => Configuration::path('bootstrap'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\' . basename($configurationPath))];
        $configuration = Configuration::open($configurationPath);
        $bootstrapConfig = Configuration::validate($schema, array_key_exists('BOOTSTRAP', $configuration) ? $configuration['BOOTSTRAP'] : [], ['configuration-path' => $configurationPath]);

        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php', 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate($bootstrapConfig['path'], '', static function (string $resourceNSPath, string $resourcePath) use ($bootstrapConfig, $configuration, $fp) {
            $context = PHP::deductContextFromFile($resourcePath);

            $configSection = '';
            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($resourceNSPath !== '') {
                $resourceNS = $bootstrapConfig['namespace'] . '\\' . $resourceNSPath;
            } else {
                $resourceNS = $bootstrapConfig['namespace'];
            }

            if ($resourceNSPath !== '') {
                $configSection = str_replace('\\', '/', $resourceNSPath) . '/';
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
            $configSection .= $identifier;

            $sectionConfiguration = [];
            if (array_key_exists($configSection, $configuration)) {
                $sectionConfiguration = $configuration[$configSection];
            }

            fwrite($fp, PHP::function($resourceNS . '\\' . $identifier, 'validate', 'array $schema', ': array', 'return \\' . Configuration::class . '::validate($schema, ' . PHP::export($sectionConfiguration) . ', ["configuration-path" => __DIR__]);'));
            fwrite($fp, PHP::function($resourceNS, $identifier, $parameters, $returnType, 'static $closure; if (!isset($closure)) { $closure = require ' . PHP::export($resourcePath) . '; }' . ($void === true ? '' : 'return ') . ' $closure(...func_get_args());'));
        });
        fclose($fp);
    }
}