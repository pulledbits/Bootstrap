<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use rikmeijer\Bootstrap\Configuration;

/** @noinspection PhpUndefinedFunctionInspection PhpUndefinedNamespaceInspection */
return binary\configure(static function (array $configuration, string ...$defaultArguments): callable {
    $pathValidator = Configuration::pathValidator(...(count($defaultArguments) > 0 ? [array_shift($defaultArguments)] : []));
    return static function (?array $configuredCommand, callable $error, array $context) use ($pathValidator, $defaultArguments, $configuration) {
        $binary = $pathValidator($configuredCommand !== null ? $configuredCommand[0] : null, $error, $context);
        $configuredArguments = Configuration::default($defaultArguments, $configuredCommand !== null ? $configuredCommand[1] : null, $error);
        if (file_exists($binary) === false) {
            $error('binary "' . $binary . '" does not exist');
            return static function (): void {
            };
        }


        $command = static function (string ...$arguments) use ($binary, $configuredArguments) {
            return escapeshellcmd($binary) . ' ' . implode(' ', array_map(static function (string $arg) {
                    return match (PHP_OS_FAMILY) {
                        'Windows' => str_starts_with($arg, '/') ? $arg : escapeshellarg($arg),
                        default => str_starts_with($arg, '-') ? $arg : escapeshellarg($arg),
                    };
                }, $arguments + $configuredArguments));
        };


        return static function (string $description, string ...$arguments) use ($command, $configuration): int {
            $in = $configuration['in']('rb');
            $out = $configuration['out']('wb');
            $err = $configuration['error']('ab');

            print $description . PHP_EOL;
            if ($configuration['simulation']) {
                fwrite($out, '(s) ' . $command(...$arguments));
                return 0;
            }

            $stderr = tmpfile();
            $descriptors = [0 => ["pipe", "rb"],    // stdin
                1 => ["pipe", "wb"],    // stdout
                2 => $stderr        // stderr
            ];
            $process = proc_open($command(...$arguments), $descriptors, $pipes);
            if (is_resource($process) === false) {
                return -1;
            }

            fwrite($pipes[0], fgets($in) ?: '');
            fclose($pipes[0]);
            while (!feof($pipes[1])) {
                fwrite($out, fread($pipes[1], 1024));
            }
            fclose($pipes[1]);

            if (ftell($stderr) > 0) {
                fseek($stderr, 0);
                fwrite($err, fread($stderr, 1024));
            }

            return proc_close($process);
        };
    };
}, ['simulation' => boolean(false), 'in' => file('php://input'), 'out' => file('php://output'), 'error' => file('php://temp'),]);