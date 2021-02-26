<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use ReflectionException;
use ReflectionFunction;

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

            $code = '\\' . Resource::class . '::require(' . PHP::export($resourcePath) . ', static function(array $schema) {
                            return \\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($resourceNSPath . basename($resourcePath, '.php')) . ', $schema);
                        })';

            try {
                $closureReflection = new ReflectionFunction(eval(sprintf("return %s;", $code)));
            } catch (ReflectionException $e) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            }
            $returnType = '';
            $void = null;
            if ($closureReflection->getReturnType() !== null) {
                $returnType = ' : ' . PHP::export($closureReflection->getReturnType());
            }

            fwrite($fp, PHP::function($resourceNS, basename($resourcePath, '.php'), array_map([PHP::class, 'export'], $closureReflection->getParameters()), $returnType, $code));
        });
        fclose($fp);
    }
}