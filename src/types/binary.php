<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\types;

use function Functional\partial_left;
use function rikmeijer\Bootstrap\configure;

return configure(static function (array $configuration, array $defaultCommand = []): callable {
    $defaultBinary = path(count($defaultCommand) > 0 ? array_shift($defaultCommand) : null);
    $defaultArguments = arr($defaultCommand);
    return static function (?array $configuredCommand, callable $error) use ($defaultBinary, $defaultArguments, $configuration) {
        if (empty($configuredCommand)) {
            $binary = $defaultBinary(null, $error);
            $configuredArguments = $defaultArguments(null, $error);
        } else {
            $binary = $defaultBinary(array_shift($configuredCommand), $error);
            $configuredArguments = $defaultArguments($configuredCommand, $error);
        }

        if (file_exists($binary) === false) {
            $error('binary "' . $binary . '" does not exist');
            return static function (): void {
            };
        }
        $command = partial_left(static function (string $binary, array $configuredArguments, string ...$arguments) {
            return escapeshellcmd($binary) . ' ' . implode(' ', array_map(static function (string $arg) {
                    return match (PHP_OS_FAMILY) {
                        'Windows' => str_starts_with($arg, '/') ? $arg : escapeshellarg($arg),
                        default => str_starts_with($arg, '-') ? $arg : escapeshellarg($arg),
                    };
                }, array_merge($configuredArguments, $arguments)));
        }, $binary, $configuredArguments);


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
            $descriptors = [
                0 => ["pipe", "rb"],    // stdin
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
}, [
    'simulation' => boolean(false),
    'in'         => file('php://input'),
    'out'        => file('php://output'),
    'error'      => file('php://temp'),
], 'types/binary');