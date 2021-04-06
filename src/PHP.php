<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

use Functional as F;
use Nette\PhpGenerator\GlobalFunction;

class PHP
{

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

    public static function extractGlobalFunctionFromFile(string $resourcesPath, string $resourcePath, string $functionName, string $genericNS, string $openFunction): string
    {

        $resourceFilePath = $resourcePath . '/' . $functionName . '.php';
        $body = '\\' . $openFunction . '(' . var_export($resourceFilePath, true) . ')';
        $namespace = $genericNS;

        $f = new GlobalFunction($functionName);
        $context = self::deductContextFromString(file_get_contents($resourcesPath . DIRECTORY_SEPARATOR . $resourceFilePath));
        if (array_key_exists('namespace', $context)) {
            $namespace = substr($context['namespace'], 1);
        }
        if (array_key_exists('functions', $context)) {
            return 'namespace ' . $namespace . ' { ' . PHP_EOL . $body . ';}';
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
        if ($returnType === null || $returnType !== 'void') {
            $body = 'return ' . $body;
        }
        $f->setBody($body . '(...func_get_args());');

        return 'namespace ' . $namespace . ' { ' . PHP_EOL . '    if (function_exists("' . $namespace . '\\' . $functionName . '") === false) {' . PHP_EOL . '    ' . $f->__toString() . PHP_EOL . '    }' . PHP_EOL . '}';
    }

    public static function interpretNamespace(callable $collector, array $context): array
    {
        $collector([T_NAME_QUALIFIED], static function (callable $namespaceTokens) use (&$context): void {
            $context['namespace'] = '\\' . ($namespaceTokens(2))[1];
        });
        return $context;
    }

    public static function interpretUse(callable $collector, array $context): array
    {
        $collector([';'], static function (callable $useTokens) use (&$context): void {
            $useTokens(-1); //pop ;
            if (array_key_exists('uses', $context) === false) {
                $context['uses'] = [];
            }

            $useIdentifier = ($useTokens(-1))[1];
            $asIdentifier = substr($useIdentifier, strrpos($useIdentifier, '\\') + 1);
            $context['uses'][$asIdentifier] = $useIdentifier;
        });
        return $context;
    }

    public static function interpretParameter(array &$context): callable
    {
        return static function (callable $parameterTokens) use (&$context): void {
            $parameter = ['nullable' => false, 'variadic' => false, 'type' => null, 'name' => null];
            $bufferedTokens = [];
            while ($parameterToken = $parameterTokens(1)) {
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
                        if (array_key_exists('uses', $context) === false) {
                            $types[] = $bufferedToken[1];
                        } elseif (array_key_exists($bufferedToken[1], $context['uses'])) {
                            $types[] = '\\' . $context['uses'][$bufferedToken[1]];
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

            while ($parameterToken = $parameterTokens(1)) {
                if (is_array($parameterToken)) {
                    $code = 'return ' . $parameterToken[1] . ';';
                    $parameter['default'] = eval($code);
                }
            }
            $context['parameters'][] = $parameter;
        };
    }

    public static function interpretParameters(callable $collector, array &$context): void
    {  // find opening parenthesis
        $collector([")"], static function (callable $parametersTokens) use (&$context): void {
            $context['parameters'] = [];
            $parametersTokens(-1); // pop )
            $parametersTokensCollector = self::makeCollectorFromTokens($parametersTokens);
            do {
                $found = $parametersTokensCollector([",", null], self::interpretParameter($context));
            } while ($found);
        });
    }

    public static function interpretReturnType(callable $collector, array $context): array
    {
        $collector(["{"], static function (callable $returnValueTokens) use (&$context) {
            $returnValueTokens(-1); // pop {
            $context['returnType'] = '';
            while (($token = $returnValueTokens(-1)) !== null) {
                if (is_string($token)) {
                    $context['returnType'] = $token . $context['returnType'];
                } elseif ($token[0] === T_WHITESPACE) {
                    continue;
                } else {
                    $context['returnType'] = trim($token[1]) . $context['returnType'];
                }
            }
        });
        return $context;
    }

    public static function interpretFunction(callable $collector, array $context): array
    {
        $collector(["("], static function (callable $functionNameTokens) use ($collector, &$context) {
            self::makeCollectorFromTokens($functionNameTokens)([
                T_STRING,
                T_NAME_QUALIFIED
            ], static function (callable $tokens) use (&$context) {
                $context['identifier'] = $tokens(-1)[1];
            });
            self::interpretParameters($collector, $context);
        });

        $collector(['{'], static function (callable $functionParameterTokens) use (&$context) {
            $returnCollector = self::makeCollectorFromTokens($functionParameterTokens);
            $returnCollector([":"], fn() => self::interpretReturnType($returnCollector, $context));
        });

        $nest = 0;
        do {
            $collector(['}'], static function (callable $functionBodyTokens) use (&$nest) {
                $nest--;
                while ($functionBodyToken = $functionBodyTokens(1)) {
                    if ($functionBodyToken === '{') {
                        $nest++;
                    }
                }
            });
        } while ($nest > 0);

        return $context;
    }

    public static function interpretReturn(callable $collector, array $context): array
    {
        $collector([T_FUNCTION], static function () use ($collector, &$context): void {
            $context = self::interpretFunction($collector, $context);
        });
        return $context;
    }

    public static function deductContextFromString(string $code): array
    {
        $tokens = self::tokenize(token_get_all($code, TOKEN_PARSE));
        $collector = self::makeCollectorFromTokens($tokens);
        $nextToken = F\partial_left($tokens, 1);
        $context = [];
        while ($parameterToken = $nextToken()) {
            $context = match (is_string($parameterToken) ? $parameterToken : $parameterToken[0]) {
                T_NAMESPACE => self::interpretNamespace($collector, $context),
                T_USE => self::interpretUse($collector, $context),
                T_RETURN => self::interpretReturn($collector, $context),
                T_FUNCTION => (static function (callable $collector, array $context) {
                    $functionContext = self::interpretFunction($collector, ['uses' => $context['uses'] ?? []]);
                    if (array_key_exists('identifier', $functionContext)) {
                        if (array_key_exists('functions', $context) === false) {
                            $context['functions'] = [];
                        }
                        unset($functionContext['uses']);
                        $context['functions'][$functionContext['identifier']] = $functionContext;
                    }
                    return $context;
                })($collector, $context),
                default => $context
            };
        }
        return $context;
    }

    private static function makeCollectorFromTokens(callable $tokens): callable
    {
        return F\partial_left([__CLASS__, 'tokenCollector'], $tokens);
    }

    public static function createMatcher(string|array $token): callable
    {
        return F\partial_right(match (is_string($token)) {
            true => static fn(int|string $id, string $token) => $token === $id,
            false => static fn(int|string $id, array $token) => $token[0] === $id
        }, $token);
    }

    public static function tokenCollector(callable $tokens, array $ids, callable $onMatch): bool
    {
        $buffer = [];
        $matchableIds = array_filter($ids, static fn(null|int|string $id) => $id !== null);
        while ($token = $tokens(1)) {
            $buffer[] = $token;
            if (F\contains(F\map($matchableIds, static fn($id) => self::createMatcher($token)($id)), true)) {
                $onMatch(self::tokenize($buffer));
                return true;
            }
        }
        if (count($buffer) === 0) {
            return false;
        }
        if (F\contains($ids, null)) {
            $onMatch(self::tokenize($buffer));
            return true;
        }
        return false;
    }
}