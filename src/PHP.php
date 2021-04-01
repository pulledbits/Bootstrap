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
        if (count($context) === 0) {
            return 'namespace ' . $namespace . ' { ' . $body . ';}';
        }
        if (array_key_exists('namespace', $context)) {
            $namespace = substr($context['namespace'], 1);
            if (count($context) === 1) {
                return 'namespace ' . $namespace . ' { ' . $body . ';}';
            }
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


        $fullyQualifiedFunctionName = $namespace . '\\' . $functionName;
        $returnType = $f->getReturnType();
        if ($fullyQualifiedFunctionName === '\rikmeijer\Bootstrap\resource\open') {
            $body = 'static $closure; if (!isset($closure)) $closure = (include __DIR__ . DIRECTORY_SEPARATOR . "resource/open.php"); return $closure';
        } else {
            if ($returnType === null || $returnType !== 'void') {
                $body = 'return ' . $body;
            }
        }
        $f->setBody($body . '(...func_get_args());');

        return 'namespace ' . $namespace . ' { ' . PHP_EOL . '    if (function_exists("' . $fullyQualifiedFunctionName . '") === false) {' . PHP_EOL . '    ' . $f->__toString() . PHP_EOL . '    }' . PHP_EOL . '}';
    }

    public static function deductContextFromString(string $code): array
    {
        $collector = self::makeCollectorFromTokens(self::tokenize(token_get_all($code, TOKEN_PARSE)));

        $tokensUptoReturn = $collector(T_RETURN);
        if ($tokensUptoReturn === null) {
            return [];
        }

        $context = [];

        $tokensUpToReturnCollector = self::makeCollectorFromTokens($tokensUptoReturn);
        if ($tokensUpToReturnCollector(T_NAMESPACE) !== null) {
            $context['namespace'] = '\\' . ($tokensUpToReturnCollector(T_NAME_QUALIFIED)(2))[1];
        }

        $uses = [];
        while ($tokensUpToReturnCollector(T_USE) !== null) {
            $useTokens = $tokensUpToReturnCollector(T_NAME_QUALIFIED);
            if ($useTokens === null) {
                continue;
            }
            $useIdentifier = ($useTokens(-1))[1];
            $asIdentifier = substr($useIdentifier, strrpos($useIdentifier, '\\') + 1);
            $uses[$asIdentifier] = $useIdentifier;
        }

        if ($collector(T_FUNCTION) === null) {
            return $context;
        }

        $collector("("); // find opening parenthesis
        $parametersTokens = $collector(")");
        $parametersTokens(-1); // pop )
        $parametersTokensCollector = self::makeCollectorFromTokens($parametersTokens);

        $context['parameters'] = [];
        while ($parameterTokens = $parametersTokensCollector(",", null)) {
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

            while ($parameterToken = $parameterTokens(1)) {
                if (is_array($parameterToken)) {
                    $code = 'return ' . $parameterToken[1] . ';';
                    $parameter['default'] = eval($code);
                }
            }

            $context['parameters'][] = $parameter;
        }

        $functionParameterTokens = $collector('{');
        $returnCollector = self::makeCollectorFromTokens($functionParameterTokens);
        if ($returnCollector(":") !== null) {
            $returnValueTokens = $returnCollector("{");
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
        }
        return $context;
    }

    private static function makeCollectorFromTokens(callable $tokens): callable
    {
        return F\partial_left([__CLASS__, 'tokenCollector'], $tokens);
    }

    private static function matchToken(mixed $id, string|array $token): bool
    {
        if (is_string($id)) {
            if ($token === $id) {
                return true;
            }
        } elseif (is_int($id)) {
            if ($token[0] === $id) {
                return true;
            }
        }
        return false;
    }

    public static function tokenCollector(callable $tokens, mixed ...$ids): ?callable
    {
        $buffer = [];
        while ($token = $tokens(1)) {
            $buffer[] = $token;
            foreach ($ids as $id) {
                if (self::matchToken($id, $token)) {
                    return self::tokenize($buffer);
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