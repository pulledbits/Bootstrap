<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;

class Resource
{
    public static function loader(string $configurationPath, array $bootstrapConfig): callable
    {
        $loader = static function (string $identifier) use ($configurationPath, $bootstrapConfig): string {
            return '\\' . self::class . '::require(' . PHP::export(Path::join($bootstrapConfig['path'], $identifier . '.php')) . ', \\' . self::class . '::loader(' . PHP::export($configurationPath) . ', ' . PHP::export($bootstrapConfig) . '), static function(array $schema) {
                            return \\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($identifier) . ', $schema);
                        });';
        };
        return static function (string $identifier, mixed ...$args) use ($bootstrapConfig, $loader): mixed {
            $function = $bootstrapConfig['namespace'] . '\\' . $identifier;
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