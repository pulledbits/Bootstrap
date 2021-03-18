<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use Nette\PhpGenerator\GlobalFunction;

final class Bootstrap
{
    private static function resources(string $configurationPath): array
    {
        return [
            __DIR__                                                => 'configuration',
            $configurationPath . DIRECTORY_SEPARATOR . 'bootstrap' => ''
        ];
    }

    private static function configurationPath(): string
    {
        $configurationPath = getenv('BOOTSTRAP_CONFIGURATION_PATH');
        if ($configurationPath === false) {
            trigger_error('EnvVar BOOTSTRAP_CONFIGURATION_PATH not found', E_USER_ERROR);
        }
        return $configurationPath;
    }

    public static function compareInodes(string $resourceDir, string $path, string $resourcesPath): bool
    {
        return fileinode($resourceDir) === fileinode($resourcesPath . DIRECTORY_SEPARATOR . $path);
    }

    public static function configure(callable $function, array $schema): callable
    {
        $configurationPath = self::configurationPath();
        $resources = F\partial_left('Functional\\head', self::resources($configurationPath));
        $resourcePath = substr(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]["file"], 0, -4);
        $resourceDir = preg_split('#[/\\\\]+#', $resourcePath);
        $configSection = [];

        do {
            array_unshift($configSection, array_pop($resourceDir));
            $path = $resources(F\partial_left([
                __CLASS__,
                'compareInodes'
            ], implode(DIRECTORY_SEPARATOR, $resourceDir)));
        } while ($path === null);
        if ($path !== '') {
            array_unshift($configSection, $path);
        }
        return F\partial_left($function, Configuration::validate($schema, $configurationPath, implode('/', $configSection)));
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


        $fp = fopen($configurationPath . DIRECTORY_SEPARATOR . 'bootstrap.php', 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate(self::resources($configurationPath), static function (string $resourcePath, string $groupNamespace) use ($bootstrapConfig, $fp) {
            $identifier = basename($resourcePath, '.php');
            $context = PHP::deductContextFromFile($resourcePath);
            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($groupNamespace !== '') {
                $resourceNS = $bootstrapConfig['namespace'] . '\\' . $groupNamespace;
            } else {
                $resourceNS = $bootstrapConfig['namespace'];
            }
            $f = new GlobalFunction($identifier);

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

            fwrite($fp, PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            fwrite($fp, $f->__toString());
            fwrite($fp, '}' . PHP_EOL);
        });
        fclose($fp);
    }

}