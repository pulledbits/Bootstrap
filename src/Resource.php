<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;

class Resource
{
    public static function loader(string $configurationPath): callable
    {
        $loader = static function (string $identifier) use ($configurationPath): string {
            $config = Bootstrap::configuration($configurationPath);
            return '\\' . self::class . '::require(' . PHP::export(Path::join($config['path'], $identifier . '.php')) . ', \\' . self::class . '::loader(' . PHP::export($configurationPath) . '), static function(array $schema) {
                            return \\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($identifier) . ', $schema);
                        });';
        };
        return static function (string $identifier, mixed ...$args) use ($configurationPath, $loader): mixed {
            $config = Bootstrap::configuration($configurationPath);
            $function = $config['namespace'] . '\\' . $identifier;
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