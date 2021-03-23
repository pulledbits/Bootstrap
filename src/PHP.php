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
        return static function () use (&$tokens): mixed {
            return array_shift($tokens);
        };
    }

    public static function extractGlobalFunctionFromFile(string $resourcePath, ?string &$functionNS = null): callable
    {
        $f = new GlobalFunction(basename($resourcePath, '.php'));
        $context = self::deductContextFromString(file_get_contents($resourcePath));

        if (array_key_exists('namespace', $context)) {
            $functionNS = $context['namespace'];
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
        return static function (string $body) use ($f) {
            $returnType = $f->getReturnType();
            if ($returnType === null || $returnType !== 'void') {
                $body = 'return ' . $body;
            }
            $f->setBody($body);
            return $f;
        };
    }

    public static function deductContextFromString(string $code): array
    {
        $tokens = self::tokenize(token_get_all($code, TOKEN_PARSE));

        $findNextToken = F\partial_left([__CLASS__, 'tokenFinder'], $tokens);
        $collectTokensUpTo = F\partial_left([__CLASS__, 'tokenCollector'], $tokens);

        $context = [];
        if ($findNextToken(T_NAMESPACE) !== null) {
            $context['namespace'] = $findNextToken(T_NAME_QUALIFIED)[1];
        } else {
            // no namespace reinit tokens
            $tokens = self::tokenize(token_get_all($code, TOKEN_PARSE));

            $findNextToken = F\partial_left([__CLASS__, 'tokenFinder'], $tokens);
            $collectTokensUpTo = F\partial_left([__CLASS__, 'tokenCollector'], $tokens);
        }

        if ($findNextToken(T_RETURN) === null) {
            return $context;
        }

        if ($findNextToken(T_FUNCTION, 10) !== null) {
            $findNextToken("(");
            $parametersTokens = F\partial_left([__CLASS__, 'tokenCollector'], $collectTokensUpTo(")"));

            $context['parameters'] = [];
            while ($parameterTokens = $parametersTokens(",", null)) {
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
                            $types[] = $bufferedToken[1];
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


            $functionSignatureTokens = $collectTokensUpTo('{');
            $functionSignatureTokenFinder = F\partial_left([__CLASS__, 'tokenFinder'], $functionSignatureTokens);
            if ($functionSignatureTokenFinder(":") !== null) {
                $context['returnType'] = '';
                while ($functionSignatureToken = $functionSignatureTokens()) {
                    $context['returnType'] .= $functionSignatureToken[1];
                }
                $context['returnType'] = trim($context['returnType']);
            }
        }
        return $context;
    }

    public static function tokenFinder(callable $tokens, mixed $id, ?int $maxTokenDistance = null): null|array|string
    {
        while ($token = $tokens()) {
            if ($maxTokenDistance > 0) {
                $maxTokenDistance--;
            } elseif ($maxTokenDistance === 0) {
                return null;
            }
            if (is_string($id)) {
                if ($token === $id) {
                    return $token;
                }
            } elseif (is_int($id)) {
                if ($token[0] === $id) {
                    return $token;
                }
            }
        }
        return null;
    }

    public static function tokenCollector(callable $tokens, mixed ...$ids): ?callable
    {
        $buffer = [];
        while ($token = $tokens()) {
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
            $buffer[] = $token;
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