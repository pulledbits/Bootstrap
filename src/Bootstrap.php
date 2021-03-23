<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use Nette\PhpGenerator\GlobalFunction;

final class Bootstrap
{
    public static function resources(string $configurationPath): array
    {
        return [
            $configurationPath . DIRECTORY_SEPARATOR . 'bootstrap' => ''
        ];
    }

    public static function configurationPath(): string
    {
        $configurationPath = getenv('BOOTSTRAP_CONFIGURATION_PATH');
        if (is_string($configurationPath) === false) {
            trigger_error('EnvVar BOOTSTRAP_CONFIGURATION_PATH not found', E_USER_ERROR);
        }
        return $configurationPath;
    }

    public static function generate(string $configurationPath): void
    {
        $schema = [
            'namespace' => F\partial_left(static function (string $defaultValue, $value) use (&$groupNamespace) {
                if ($value !== null) {
                    $groupNamespace = '';
                    return $value;
                }
                return $defaultValue;
            }, __NAMESPACE__ . '\\' . basename($configurationPath))
        ];
        $bootstrapConfig = Configuration::validate($schema, $configurationPath, 'BOOTSTRAP');

        self::generateResources(self::resources($configurationPath), $bootstrapConfig['namespace'], $configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php');
    }

    public static function generateResources(array $resources, string $bootstrapNS, string $targetPath): void
    {
        $fp = fopen($targetPath, 'wb');
        $write = F\partial_left('\\fwrite', $fp);
        $write('<?php declare(strict_types=1);' . PHP_EOL);
        Resource::generate($resources, F\partial_left(static function (string $bootstrapNS, callable $write, string $resourcePath, string $groupNamespace) {
            $f = new GlobalFunction(basename($resourcePath, '.php'));

            $context = PHP::deductContextFromFile($resourcePath);
            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } else {
                $resourceNS = $bootstrapNS;
                if ($groupNamespace !== '') {
                    $resourceNS .= '\\' . $groupNamespace;
                }
            }

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

            $write(PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            $write($f->__toString());
            $write('}' . PHP_EOL);
        }, $bootstrapNS, $write));
        fclose($fp);
    }

}