<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class PHP
{
    public static function function (string $namespace, string $function, array $parameters, string $returnType, string $code): string
    {
        $void = str_ends_with($returnType, 'void');
        $fqfn = $namespace . '\\' . str_replace('/', '\\', $function);
        return PHP_EOL . 'namespace ' . $namespace . ' { ' . PHP_EOL . '    if (function_exists(' . self::export($fqfn) . ') === false) {' . PHP_EOL . '        function ' . $function . ' (' . implode(', ', $parameters) . ') ' . $returnType . ' {' . PHP_EOL . '            ' . ($void === true ? '' : 'return ') . $code . '(...func_get_args());' . PHP_EOL . '        }' . PHP_EOL . '    }' . PHP_EOL . '}' . PHP_EOL;
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

            if ($variable->isDefaultValueAvailable() === false) {
                $default = '';
            } elseif ($variable->isDefaultValueConstant()) {
                $default = ' = ' . $variable->getDefaultValueConstantName();
            } else {
                $default = ' = ' . self::export($variable->getDefaultValue());
            }

            return $typeHint . ' $' . $variable->getName() . $default;
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