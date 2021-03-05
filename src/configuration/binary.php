<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use Generator;
use rikmeijer\Bootstrap\Configuration;

return static function (string ...$defaultValue): callable {
    $pathValidator = Configuration::pathValidator(...$defaultValue);
    return static function (mixed $value, callable $error, array $context) use ($pathValidator) {
        $binary = $pathValidator($value, $error, $context);
        if (file_exists($binary) === false) {
            $error('binary "' . $binary . '" does not exist');
            return static function (): void {
            };
        }
        return static function (string ...$arguments) use ($binary): Generator {
            $escapedArguments = array_map('escapeshellarg', $arguments);
            $command = '"' . $binary . '" ' . implode(' ', $escapedArguments);
            $process = proc_open($command, [STDIN,  // stdin is a pipe that the child will read from
                ['pipe', 'w'],  // stdout is a pipe that the child will write to
                STDERR // stderr is a file to write to
            ], $pipes);

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
};