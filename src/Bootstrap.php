<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;

final class Bootstrap
{
    public static function generate(string $configurationPath): void
    {
        $resources = [__DIR__ . DIRECTORY_SEPARATOR . 'configuration' => 'configuration'];
        $groupNamespace = basename($configurationPath);
        $schema = ['path' => Configuration::pathValidator('bootstrap'), 'namespace' => F\partial_left(static function (string $defaultValue, $value) use (&$groupNamespace) {
            if ($value !== null) {
                $groupNamespace = '';
                return $value;
            }
            return $defaultValue;
        }, __NAMESPACE__)];
        $configuration = Configuration::open($configurationPath);
        $bootstrapConfig = Configuration::validate($schema, $configuration('BOOTSTRAP'), ['configuration-path' => $configurationPath]);
        $resources[$bootstrapConfig['path']] = $groupNamespace;


        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php', 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate($resources, static function (string $resourcePath, string $group, string $groupNamespace) use ($bootstrapConfig, $configuration, $fp) {
            $context = PHP::deductContextFromFile($resourcePath);

            $configSection = '';
            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($groupNamespace !== '') {
                $resourceNS = $bootstrapConfig['namespace'] . '\\' . $groupNamespace;
            } else {
                $resourceNS = $bootstrapConfig['namespace'];
            }

            if ($group !== '') {
                $configSection = $group . '/';
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
            fwrite($fp, PHP_EOL . 'use Functional as F;');
            fwrite($fp, PHP::function($fqfn . '\\configure', 'callable $function, array $schema', ': callable', 'return F\\partial_left($function, Configuration::validate($schema, ' . PHP::export($configuration($configSection)) . ', ["configuration-path" => __DIR__]));'));
            fwrite($fp, '}' . PHP_EOL);
            fwrite($fp, PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            fwrite($fp, PHP::function($fqfn, $parameters, $returnType, 'static $closure; if (!isset($closure)) { $closure = require ' . PHP::export($resourcePath) . '; }' . ($void === true ? '' : 'return ') . ' $closure(...func_get_args());'));
            fwrite($fp, '}' . PHP_EOL);
        });
        fclose($fp);
    }

}