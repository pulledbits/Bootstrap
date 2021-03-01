<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap;

final class Bootstrap
{
    public static function initialize(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        if (file_exists($config['functions-path'])) {
            require $config['functions-path'];
        }
    }

    public static function configuration(string $configurationPath): array
    {
        return Configuration::open($configurationPath, 'BOOTSTRAP', ['path' => Configuration::path('bootstrap'), 'functions-path' => Configuration::path('_f.php'), 'namespace' => Configuration::default(__NAMESPACE__ . '\\' . basename($configurationPath))]);
    }

    private static function tokenFinder(array &$tokens): callable
    {
        return static function (mixed $id, ?int $maxTokenDistance = null) use (&$tokens): null|array|string {
            while ($token = array_shift($tokens)) {
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
        };
    }

    private static function tokenCollector(array &$tokens): callable
    {
        return static function (mixed $id) use (&$tokens): null|array {
            $buffer = [];
            while ($token = array_shift($tokens)) {
                if (is_string($id)) {
                    if ($token === $id) {
                        return $buffer;
                    }
                } elseif (is_int($id)) {
                    if ($token[0] === $id) {
                        return $buffer;
                    }
                }
                $buffer[] = $token;
            }
            array_unshift($tokens, ...$buffer);
            return null;
        };
    }

    public static function generate(string $configurationPath): void
    {
        $config = self::configuration($configurationPath);
        $resourcesNS = $config['namespace'];
        $fp = fopen($config['functions-path'], 'wb');
        fwrite($fp, '<?php' . PHP_EOL);
        Resource::generate($config['path'], '', static function (string $resourceNSPath, string $resourcePath) use ($configurationPath, $resourcesNS, $fp) {
            $resourceFileContents = file_get_contents($resourcePath);

            $tokens = token_get_all($resourceFileContents, TOKEN_PARSE);

            $findNextToken = self::tokenFinder($tokens);

            $getUpToNextToken = self::tokenCollector($tokens);

            $context = ['parameters' => ''];

            while ($token = array_shift($tokens)) {
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $context['namespace'] = $findNextToken(T_NAME_QUALIFIED)[1];
                        break;

                    case T_RETURN:
                        if ($findNextToken(T_FUNCTION, 3) !== null) {
                            $findNextToken("(");
                            foreach ($getUpToNextToken(")") as $parameterToken) {
                                if (is_string($parameterToken)) {
                                    $context['parameters'] .= $parameterToken;
                                } else {
                                    $context['parameters'] .= $parameterToken[1];
                                }
                            }


                            $functionSignatureTokens = $getUpToNextToken('{');
                            $functionSignatureTokenFinder = self::tokenFinder($functionSignatureTokens);
                            if ($functionSignatureTokenFinder(":") !== null) {
                                $context['returnType'] = ':';
                                while ($functionSignatureToken = array_shift($functionSignatureTokens)) {
                                    $context['returnType'] .= $functionSignatureToken[1];
                                }
                            }
                        }
                        break;

                    default:
                        // ignore
                        break;
                }
            }

            if (array_key_exists('namespace', $context)) {
                $resourceNS = $context['namespace'];
            } elseif ($resourceNSPath !== '') {
                $resourceNS = $resourcesNS . '\\' . $resourceNSPath;
            } else {
                $resourceNS = $resourcesNS;
            }


            $configCode = '\\' . Configuration::class . '::open(' . PHP::export($configurationPath) . ', ' . PHP::export($resourceNSPath . basename($resourcePath, '.php')) . ', $schema);';

            fwrite($fp, PHP::function($resourceNS . '\\' . basename($resourcePath, '.php'), 'validate', 'array $schema', ': array', $configCode));
            fwrite($fp, PHP::function($resourceNS, basename($resourcePath, '.php'), $context['parameters'], array_key_exists('returnType', $context) ? rtrim($context['returnType']) : '', '\\' . Resource::class . '::require(' . PHP::export($resourcePath) . ', static function(array $schema) {
                            return ' . $configCode . '
                        })(...func_get_args());'));
        });
        fclose($fp);
    }
}