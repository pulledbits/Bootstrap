<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use Generator;
use rikmeijer\Bootstrap\Configuration;

/** @noinspection PhpUndefinedFunctionInspection PhpUndefinedNamespaceInspection */
return binary\configure(static function (array $configuration, string ...$defaultValue): callable {
    $pathValidator = Configuration::pathValidator(...$defaultValue);
    return static function (mixed $value, callable $error, array $context) use ($pathValidator, $configuration) {
        $binary = $pathValidator($value, $error, $context);
        if (file_exists($binary) === false) {
            $error('binary "' . $binary . '" does not exist');
            return static function (): void {
            };
        }

        $command = static function (string ...$arguments) use ($binary) {
            return escapeshellcmd($binary) . ' ' . implode(' ', array_map(static function (string $arg) {
                    return match (PHP_OS_FAMILY) {
                        'Windows' => str_starts_with($arg, '/') ? $arg : escapeshellarg($arg),
                        default => str_starts_with($arg, '-') ? $arg : escapeshellarg($arg),
                    };
                }, $arguments));
        };

        return static function (string ...$arguments) use ($command, $configuration): Generator {
            if ($configuration['simulation']) {
                yield '(s) ' . $command(...$arguments);
                return;
            }
            $process = proc_open($command(...$arguments), [STDIN, ['pipe', 'w'], STDERR], $pipes);

            if ($process !== false) {
                while (feof($pipes[1]) === false) {
                    $buffer = fgets($pipes[1]);
                    if ($buffer !== false) {
                        yield $buffer;
                    }
                }
                fclose($pipes[1]);
                proc_close($process);
            }
        };
    };
}, ['simulation' => boolean(false)]);