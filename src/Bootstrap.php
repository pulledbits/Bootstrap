<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use Nette\PhpGenerator\GlobalFunction;

final class Bootstrap
{
    public static function generate(string $configurationPath): void
    {
        $resources = [__DIR__ => 'configuration'];
        $schema = ['path' => Configuration::pathValidator('bootstrap'), 'namespace' => F\partial_left(static function (string $defaultValue, $value) use (&$groupNamespace) {
            if ($value !== null) {
                $groupNamespace = '';
                return $value;
            }
            return $defaultValue;
        }, __NAMESPACE__ . '\\' . basename($configurationPath))];
        $bootstrapConfig = Configuration::validate($schema, $configurationPath, 'BOOTSTRAP');
        $resources[$bootstrapConfig['path']] = '';


        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php', 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate($resources, static function (string $resourcePath, string $group, string $groupNamespace) use ($bootstrapConfig, $fp) {
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
            $f_configure = new GlobalFunction('configure');
            $f_configure->addParameter('function', 'callable');
            $f_configure->addParameter('schema', 'array');
            $f_configure->setBody('return F\\partial_left($function, Configuration::validate($schema, __DIR__, ' . PHP::export($configSection) . '));');
            $f_configure->setReturnType('callable');
            fwrite($fp, $f_configure->__toString());
            fwrite($fp, PHP_EOL . '}');

            fwrite($fp, PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            fwrite($fp, PHP::function($fqfn, $parameters, $returnType, 'static $closure; if (!isset($closure)) { $closure = require ' . PHP::export($resourcePath) . '; }' . ($void === true ? '' : 'return ') . ' $closure(...func_get_args());'));
            fwrite($fp, '}' . PHP_EOL);
        });
        fclose($fp);
    }

}