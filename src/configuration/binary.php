<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

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

        return static function (string $description, string ...$arguments) use ($command, $configuration, $error): int {
            print $description . PHP_EOL;
            if ($configuration['simulation']) {
                print '(s) ' . $command(...$arguments);
                return 0;
            }
            $descriptors = [0 => ["pipe", "rb"],    // stdin
                1 => ["pipe", "wb"],    // stdout
                2 => ["pipe", "wb"]        // stderr
            ];
            $process = proc_open($command(...$arguments), $descriptors, $pipes);
            if (is_resource($process) === false) {
                return -1;
            }

            fclose($pipes[0]);
            $stderr = '';
            while (!feof($pipes[1]) || !feof($pipes[2])) {
                print fread($pipes[1], 1024);
                $stderr .= fread($pipes[2], 1024);
            }
            fclose($pipes[1]);
            fclose($pipes[2]);

            if (empty($stderr) === false) {
                $error($stderr);
            }

            return proc_close($process);
        };
    };
}, ['simulation' => boolean(false)]);