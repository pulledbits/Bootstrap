<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;

class Resource
{
    public static function loader(string $configurationPath): callable
    {
        $config = Bootstrap::configuration($configurationPath);
        $resourcesPath = $config['path'];
        $functionsNS = $config['namespace'];
        $loader = static function (string $identifier) use ($configurationPath, $resourcesPath, $functionsNS): string {
            return '\\' . self::class . '::require(' . PHP::export(Path::join($resourcesPath, $identifier . '.php')) . ', \\' . self::class . '::loader(' . PHP::export($configurationPath) . ', ' . PHP::export($resourcesPath) . ', ' . PHP::export($functionsNS) . '), static function(array $schema) {
                            return \\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($identifier) . ', $schema);
                        });';
        };
        return static function (string $identifier, mixed ...$args) use ($functionsNS, $loader): mixed {
            $function = $functionsNS . '\\' . $identifier;
            if (function_exists($function) === false) {
                eval(PHP::wrapResource($function, $loader($identifier)));
            }
            return $function(...$args);
        };
    }

    /**
     * The purpose of the method is shielding other variables from the included script
     * @param string $path
     * @param callable $bootstrap
     * @param callable $validate
     * @return mixed
     * @noinspection PhpIncludeInspection PhpUnusedParameterInspection
     */
    public static function require(string $path, callable $bootstrap, callable $validate): callable
    {
        return (require $path);
    }
}