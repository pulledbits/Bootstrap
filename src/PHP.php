<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class PHP
{
    public static function wrapResource(string $namespace, string $function, string $code): string
    {
        try {
            $closureReflection = new ReflectionFunction(eval('return ' . $code));
        } catch (ReflectionException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        $returnType = '';
        $void = null;
        if ($closureReflection->getReturnType() !== null) {
            $returnType = ' : ' . self::export($closureReflection->getReturnType());
            $void = $returnType === ' : void';
        }

        $fqfn = $namespace . '\\' . str_replace('/', '\\', $function);
        return PHP_EOL . 'namespace ' . $namespace . ' { ' . PHP_EOL . '    if (function_exists(' . self::export($fqfn) . ') === false) {' . PHP_EOL . '        function ' . $function . ' (' . implode(', ', array_map([self::class, 'export'], $closureReflection->getParameters())) . ') ' . $returnType . ' {' . PHP_EOL . '            static $closure;' . PHP_EOL . '            if (isset($closure) === false) {' . PHP_EOL . '                $closure = ' . $code . PHP_EOL . '            }' . PHP_EOL . '        ' . ($void === true ? '' : 'return ') . '$closure(...func_get_args());' . PHP_EOL . '        }' . PHP_EOL . '    }' . PHP_EOL . '}' . PHP_EOL;
    }

    public static function export(mixed $variable): string
    {
        if ($variable instanceof ReflectionParameter) {
            $type = $variable->getType();
            if ($type === null) {
                return '$' . $variable->getName();
            }
            $typeHint = '';
            if ($type->allowsNull()) {
                $typeHint .= '?';
            }
            $typeHint .= self::export($type);
            return $typeHint . ' $' . $variable->getName();
        }

        if ($variable instanceof ReflectionUnionType) {
            return implode('|', array_map([self::class, 'export'], $variable->getTypes()));
        }

        if ($variable instanceof ReflectionNamedType) {
            return ($variable->isBuiltin() === false ? '\\' : '') . $variable->getName();
        }
        return var_export($variable, true);
    }

}