<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


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
}