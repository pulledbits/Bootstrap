<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function generate(string $configurationPath): void
    {
        $schema = ['path' => Configuration::path('bootstrap'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\' . basename($configurationPath))];
        $configuration = Configuration::open($configurationPath);
        $bootstrapConfig = Configuration::validate($schema, $configuration('BOOTSTRAP'), ['configuration-path' => $configurationPath]);

        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php', 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        fwrite($fp, PHP_EOL . 'namespace ' . $bootstrapConfig['namespace'] . '\\configuration {');
        fwrite($fp, PHP_EOL . 'use \\' . Configuration::class . ';');
        fwrite($fp, PHP_EOL . 'use Functional as F;');
        fwrite($fp, PHP::function($bootstrapConfig['namespace'] . '\\configuration\\string', 'string $defaultValue', ': callable', 'return Configuration::default($defaultValue);'));
        fwrite($fp, PHP::function($bootstrapConfig['namespace'] . '\\configuration\\path', 'string ...$defaultValue', ': callable', 'return F\partial_right(Configuration::path(...$defaultValue), ["configuration-path" => __DIR__]);'));
        fwrite($fp, PHP_EOL . '}');
        Resource::generate([$bootstrapConfig['path']], static function (string $resourceNSPath, string $resourcePath) use ($bootstrapConfig, $configuration, $fp) {
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
            $fqfn = $resourceNS . '\\' . $identifier;
            $configSection .= $identifier;
            fwrite($fp, PHP_EOL . 'namespace ' . $fqfn . ' { ');
            fwrite($fp, PHP_EOL . 'use \\' . Configuration::class . ';');
            fwrite($fp, PHP::function($fqfn . '\\validate', 'array $schema', ': array', 'return Configuration::validate($schema, ' . PHP::export($configuration($configSection)) . ', ["configuration-path" => __DIR__]);'));
            fwrite($fp, '}' . PHP_EOL);
            fwrite($fp, PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            fwrite($fp, PHP::function($fqfn, $parameters, $returnType, 'static $closure; if (!isset($closure)) { $closure = require ' . PHP::export($resourcePath) . '; }' . ($void === true ? '' : 'return ') . ' $closure(...func_get_args());'));
            fwrite($fp, '}' . PHP_EOL);
        });
        fclose($fp);
    }

}