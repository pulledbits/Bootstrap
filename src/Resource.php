<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Webmozart\PathUtil\Path;

class Resource
{
    private static function makeLoader(string $configurationPath): callable
    {
        return static function (string $identifier) use ($configurationPath): string {
            $config = Bootstrap::configuration($configurationPath);
            return '\\' . self::class . '::require(' . PHP::export(Path::join($config['path'], $identifier . '.php')) . ', \\' . self::class . '::loader(' . PHP::export($configurationPath) . '), static function(array $schema) {
                            return \\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($identifier) . ', $schema);
                        });';
        };
    }

    public static function loader(string $configurationPath): callable
    {
        $loader = self::makeLoader($configurationPath);
        return static function (string $identifier, mixed ...$args) use ($configurationPath, $loader): mixed {
            $config = Bootstrap::configuration($configurationPath);
            $function = $config['namespace'] . '\\' . str_replace('/', '\\', $identifier);
            eval(PHP::wrapResource($function, $loader($identifier)));
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

    public static function generate(mixed $resourcesPath, string $configurationPath): void
    {
        $configuration = Bootstrap::configuration($configurationPath);
        $loader = self::makeLoader($configurationPath);
        foreach (glob($resourcesPath . DIRECTORY_SEPARATOR . '*') as $resourcePath) {
            if (is_dir($resourcePath)) {
                self::generate($resourcePath, $configurationPath);
                continue;
            }

            if (str_ends_with($resourcePath, '.php')) {
                $identifier = substr(str_replace($configuration['path'], '', $resourcePath), 1, -4);
                $function = $configuration['namespace'] . '\\' . str_replace('/', '\\', $identifier);
                $fp = fopen($configuration['functions-path'], 'ab');
                fwrite($fp, PHP::wrapResource($function, $loader($identifier)));
                fclose($fp);
            }
        }
    }
}