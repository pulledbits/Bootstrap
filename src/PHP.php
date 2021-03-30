<?php declare(strict_types=1);


namespace rikmeijer\Bootstrap;


use Functional as F;
use Nette\PhpGenerator\GlobalFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class PHP
{
    public static function function (string $fqfn, string $parameters, string $returnType, string $code): string
    {
        return PHP_EOL . '    if (function_exists(' . self::export($fqfn) . ') === false) {' . PHP_EOL . '        function ' . F\last(explode('\\', $fqfn)) . ' (' . $parameters . ') ' . ($returnType !== '' ? ': ' . $returnType : '') . '{' . PHP_EOL . '            ' . $code . PHP_EOL . '        }' . PHP_EOL . '    }' . PHP_EOL;
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

    private static function tokenize(array $tokens): callable
    {
        return static function (int $offset) use (&$tokens): mixed {
            $function = '\array_' . ($offset > 0 ? 'shift' : 'pop');
            $value = null;
            for ($i = 0; $i < abs($offset); $i++) {
                $value = $function($tokens);
            }
            return $value;
        };
    }

    public static function extractGlobalFunctionFromFile(string $resourcePath, string $genericNS, string $openFunction): string
    {
        $f = new GlobalFunction(basename($resourcePath, '.php'));
        $context = self::deductContextFromString(file_get_contents($resourcePath));

        $namespace = $genericNS;
        if (array_key_exists('namespace', $context)) {
            $namespace = $context['namespace'];
        }

        if (array_key_exists('parameters', $context)) {
            F\each($context['parameters'], static function (array $contextParameter, int $index) use ($f) {
                if ($index === 0 && str_contains($contextParameter['name'], '$configuration')) {
                    return;
                }

                if ($contextParameter['variadic']) {
                    $f->setVariadic(true);
                }
                $parameter = $f->addParameter(substr($contextParameter['name'], 1));
                $parameter->setType($contextParameter['type']);
                $parameter->setNullable($contextParameter['nullable']);

                if (array_key_exists('default', $contextParameter)) {
                    $parameter->setDefaultValue($contextParameter['default']);
                }
            });
        }

        if (array_key_exists('returnType', $context)) {
            $f->setReturnType($context['returnType']);
        }


        $returnType = $f->getReturnType();
        if ($namespace . '\\' . $f->getName() === 'rikmeijer\Bootstrap\resource\open') {
            $body = 'static $closure; if (!isset($closure)) $closure = (include __DIR__ . DIRECTORY_SEPARATOR . "resource/open.php"); return $closure';
        } else {
            $body = '\\' . $openFunction . '(' . self::export($resourcePath) . ')';
            if ($returnType === null || $returnType !== 'void') {
                $body = 'return ' . $body;
            }
        }
        $f->setBody($body . '(...func_get_args());');

        return 'namespace ' . $namespace . ' { ' . PHP_EOL . '    if (function_exists("' . $namespace . '\\' . $f->getName() . '") === false) {' . PHP_EOL . '    ' . $f->__toString() . PHP_EOL . '    }' . PHP_EOL . '}';
    }

    public static function deductContextFromString(string $code): array
    {
        $collector = self::collect(self::tokenize(token_get_all($code, TOKEN_PARSE)));

        $tokensUptoReturn = $collector(T_RETURN);
        if ($tokensUptoReturn === null) {
            return [];
        }

        $context = [];

        $tokensUpToReturnCollector = self::collect($tokensUptoReturn);
        if ($tokensUpToReturnCollector(T_NAMESPACE) !== null) {
            $context['namespace'] = ($tokensUpToReturnCollector(T_NAME_QUALIFIED)(2))[1];
        }

        $uses = [];
        while ($tokensUpToReturnCollector(T_USE) !== null) {
            $useIdentifier = ($tokensUpToReturnCollector(T_NAME_QUALIFIED)(2))[1];
            $asIdentifier = substr($useIdentifier, strrpos($useIdentifier, '\\') + 1);
            $uses[$asIdentifier] = $useIdentifier;
        }

        if ($collector(T_FUNCTION) !== null) {
            $parametersTokens = self::collect($collector(")"));
            $parametersTokens(1); // shift (
            $parametersTokens(-1); // pop )

            $context['parameters'] = [];
            while ($parameterTokens = $parametersTokens(",", ")")) {
                $parameter = ['nullable' => false, 'variadic' => false, 'type' => null, 'name' => null];
                $bufferedTokens = [];
                while ($parameterToken = $parameterTokens()) {
                    if ($parameterToken === '?') {
                        $parameter['nullable'] = true;
                        continue;
                    }

                    if (is_array($parameterToken) && $parameterToken[0] === T_WHITESPACE) {
                        continue;
                    }
                    if (is_array($parameterToken) && $parameterToken[0] === T_ELLIPSIS) {
                        $parameter['variadic'] = true;
                        continue;
                    }

                    if (is_array($parameterToken) && $parameterToken[0] === T_VARIABLE) {
                        $parameter['name'] = $parameterToken[1];
                        $types = [];
                        foreach ($bufferedTokens as $bufferedToken) {
                            if ($bufferedToken === '|') {
                                continue;
                            }

                            if (array_key_exists($bufferedToken[1], $uses)) {
                                $types[] = '\\' . $uses[$bufferedToken[1]];
                            } else {
                                $types[] = $bufferedToken[1];
                            }
                        }
                        if (count($types) > 0) {
                            $parameter['type'] = implode('|', $types);
                        }
                        break;
                    }
                    $bufferedTokens[] = $parameterToken;
                }

                while ($parameterToken = $parameterTokens()) {
                    if (is_array($parameterToken)) {
                        $code = 'return ' . $parameterToken[1] . ';';
                        $parameter['default'] = eval($code);
                    }
                }

                $context['parameters'][] = $parameter;
            }


            $functionSignatureTokens = $collector('{');
            $functionSignatureTokens(-1);
            $functionSignatureTokenFinder = self::collect($functionSignatureTokens);
            if (($functionParameterTokens = $functionSignatureTokenFinder(":", null)) !== null) {
                $functionParameterTokens(-1);

                $context['returnType'] = '';
                while ($functionSignatureToken = $functionParameterTokens(1)) {
                    $context['returnType'] .= $functionSignatureToken[1];
                }
                $context['returnType'] = trim($context['returnType']);
            }
        }
        return $context;
    }

    private static function collect(callable $tokens): callable
    {
        return F\partial_left([__CLASS__, 'tokenCollector'], $tokens);
    }

    public static function tokenCollector(callable $tokens, mixed ...$ids): ?callable
    {
        $buffer = [];
        while ($token = $tokens(1)) {
            $buffer[] = $token;
            foreach ($ids as $id) {
                if (is_string($id)) {
                    if ($token === $id) {
                        return self::tokenize($buffer);
                    }
                } elseif (is_int($id)) {
                    if ($token[0] === $id) {
                        return self::tokenize($buffer);
                    }
                }
            }
        }
        if (count($buffer) === 0) {
            return null;
        }
        if (F\contains($ids, null)) {
            return self::tokenize($buffer);
        }
        return null;
    }
}