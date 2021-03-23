<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;

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
            $f = PHP::extractGlobalFunctionFromFile($resourcePath, $functionNS);

            if ($functionNS !== null) {
                $resourceNS = $functionNS;
            } else {
                $resourceNS = $bootstrapNS;
                if ($groupNamespace !== '') {
                    $resourceNS .= '\\' . $groupNamespace;
                }
            }

            $write(PHP_EOL . 'namespace ' . $resourceNS . ' { ');
            $write($f('\\' . Resource::class . '::open(' . PHP::export($resourcePath) . ')(...func_get_args());')->__toString());
            $write('}' . PHP_EOL);
        }, $bootstrapNS, $write));
        fclose($fp);
    }

}