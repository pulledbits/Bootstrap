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
            $process = proc_open(escapeshellcmd($binary) . ' ' . implode(' ', array_map(static function (string $arg) {
                    return match (PHP_OS_FAMILY) {
                        'Windows' => str_starts_with($arg, '/') ? $arg : escapeshellarg($arg),
                        default => str_starts_with($arg, '-') ? $arg : escapeshellarg($arg),
                    };
                }, $arguments)), [STDIN, ['pipe', 'w'], STDERR], $pipes);

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