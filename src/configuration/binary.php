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

        $in = static function (): string {
            static $in;
            if (isset($in) === false) {
                $in = fopen('php://input', 'rb');
            }

            return fgets($in) ?: '';
        };
        $out = static function (string $message): int {
            static $in;
            if (isset($in) === false) {
                $out = fopen('php://output', 'wb');
            }

            return fwrite($out, $message);
        };

        return static function (string $description, string ...$arguments) use ($command, $configuration, $in, $out, $error): int {
            print $description . PHP_EOL;
            if ($configuration['simulation']) {
                $out('(s) ' . $command(...$arguments));
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

            fwrite($pipes[0], $in());
            fclose($pipes[0]);
            while (!feof($pipes[1])) {
                $out(fread($pipes[1], 1024));
            }
            fclose($pipes[1]);

            if (ftell($stderr) > 0) {
                fseek($stderr, 0);
                $error(fread($stderr, 1024));
            }

            return proc_close($process);
        };
    };
}, ['simulation' => boolean(false)]);