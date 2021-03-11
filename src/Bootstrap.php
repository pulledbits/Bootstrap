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
            $identifier = basename($resourcePath, '.php');

            $context = PHP::deductContextFromFile($resourcePath);

            $configSection = '';
            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($groupNamespace !== '') {
                $resourceNS = $bootstrapConfig['namespace'] . '\\' . $groupNamespace;
            } else {
                $resourceNS = $bootstrapConfig['namespace'];
            }
            $fqfn = $resourceNS . '\\' . $identifier;
            $f = new GlobalFunction(F\last(explode('\\', $fqfn)));

            if ($group !== '') {
                $configSection = $group . '/';
            }
            $configSection .= $identifier;
            if (array_key_exists('parameters', $context)) {
                F\each($context['parameters'], static function (array $contextParameter, int $index) use ($f) {
                    if ($index === 0 && str_contains($contextParameter['name'], '$configuration')) {
                        return;
                    }

                    if ($contextParameter['variadic']) {
                        $f->setVariadic(true);
                    }
                    $parameter = $f->addParameter(substr($contextParameter['name'], 1));
                    $parameter->setType($contextParameter['type']);
                    $parameter->setNullable($contextParameter['nullable']);

                    if (array_key_exists('default', $contextParameter)) {
                        $parameter->setDefaultValue($contextParameter['default']);
                    }
                });
            }

            $returns = true;
            if (array_key_exists('returnType', $context)) {
                $returns = str_contains($context['returnType'], 'void') === false;
                $f->setReturnType($context['returnType']);
            }

            $body = '';
            if ($returns) {
                $body .= 'return ';
            }
            $body .= '\\' . Resource::class . '::open(' . PHP::export($resourcePath) . ')(...func_get_args());';
            $f->setBody($body);

            fwrite($fp, PHP_EOL . 'namespace ' . $fqfn . ' { ');
            fwrite($fp, PHP_EOL . 'use \\' . Configuration::class . ';');
            fwrite($fp, PHP_EOL . 'use Functional as F;');
            $f_configure = new GlobalFunction('configure');
            $f_configure->addParameter('function')->setType('callable');
            $f_configure->addParameter('schema')->setType('array');
            $f_configure->setBody('return F\\partial_left($function, Configuration::validate($schema, __DIR__, ' . PHP::export($configSection) . '));');
            $f_configure->setReturnType('callable');
            fwrite($fp, $f_configure->__toString());
            fwrite($fp, PHP_EOL . '}');

            fwrite($fp, PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            fwrite($fp, $f->__toString());
            fwrite($fp, '}' . PHP_EOL);
        });
        fclose($fp);
    }

}