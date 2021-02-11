<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


use Closure;
use ReflectionAttribute;
use ReflectionException;
use ReflectionFunction;

class Resource
{
    public static function arguments(callable $bootstrap, callable $resource): array
    {
        try {
            $reflection = new ReflectionFunction($resource);
            if ($reflection->getNumberOfParameters() === 0) {
                return [];
            }
        } catch (ReflectionException $e) {
            trigger_error($e->getMessage());
            return [];
        }

        $arguments = [];
        $attributes = $reflection->getAttributes();
        array_walk($attributes, static function (ReflectionAttribute $attribute) use ($bootstrap, &$arguments) {
            $arguments = array_merge($arguments, match ($attribute->getName()) {
                Dependency::class => array_map($bootstrap, $attribute->getArguments())
            });
        });
        return $arguments;
    }

    public static function wrap(callable $configuration): callable
    {
        return new class(self::loader($configuration)) { // use class since PHP does not natively support a closure calling itself
            public function __construct(private Closure $resources)
            {
            }

            public function __invoke(string $identifier): mixed
            {
                $resource = ($this->resources)($identifier);
                return $resource(...Resource::arguments($this, $resource));
            }
        };
    }

    private static function loader(callable $config): callable
    {
        $resourcesCache = [];
        return static function (string $identifier) use ($config, &$resourcesCache): callable {
            if (array_key_exists($identifier, $resourcesCache)) {
                return $resourcesCache[$identifier];
            }
            return $resourcesCache[$identifier] = self::open(Bootstrap::resourcesPath($config) . DIRECTORY_SEPARATOR . $identifier . '.php', $config($identifier, []));
        };
    }

    /** @noinspection PhpIncludeInspection PhpUnusedParameterInspection */
    private static function open(string $path, array $configuration): callable
    {
        return require $path;
    }
}