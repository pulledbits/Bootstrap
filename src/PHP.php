<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class PHP
{
    public static function wrapResource(string $fqfn, string $code): string
    {
        try {
            $closureReflection = new ReflectionFunction(eval('return ' . $code));
        } catch (ReflectionException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        $returnType = '';
        if ($closureReflection->getReturnType() !== null) {
            $returnType = ' : ' . self::export($closureReflection->getReturnType());
        }

        $positionLastNSSeparator = strrpos($fqfn, '\\');
        $namespace = '';
        if ($positionLastNSSeparator !== false) {
            $namespace .= substr($fqfn, 0, $positionLastNSSeparator);
            $function = substr($fqfn, $positionLastNSSeparator + 1);
        }

        return 'namespace ' . $namespace . ' { if (function_exists(' . self::export($fqfn) . ') === false) {  function ' . $function . ' (' . implode(', ', array_map([self::class, 'export'], $closureReflection->getParameters())) . ') ' . $returnType . ' {
                    static $closure;
                    if (isset($closure) === false) {
                        $closure = ' . $code . '
                    }
                    return $closure(...func_get_args());
                } } }';
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