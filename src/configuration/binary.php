<?php declare(strict_types=1);

use rikmeijer\Bootstrap\Configuration;

return static function (string ...$defaultValue): callable {
    $pathValidator = Configuration::pathValidator(...$defaultValue);
    return static function (mixed $value, callable $error, array $context) use ($pathValidator) {
        $binary = $pathValidator($value, $error, $context);
        if (file_exists($binary) === false) {
            $error('binary "' . $binary . '" does not exist');
            return static function (): ?string {
                return null;
            };
        }
        return static function (string ...$arguments) use ($binary): ?string {
            $escapedArguments = array_map('escapeshellarg', $arguments);
            $command = '"' . $binary . '" ' . implode(' ', $escapedArguments);
            $process = proc_open($command, [STDIN,  // stdin is a pipe that the child will read from
                ['pipe', 'w'],  // stdout is a pipe that the child will write to
                STDERR // stderr is a file to write to
            ], $pipes);
            if ($process === false) {
                return null;
            }

            $output = '';
            while (feof($pipes[1]) === false) {
                $output .= fgets($pipes[1]);
            }
            fclose($pipes[1]);
            proc_close($process);

            return $output;
        };
    };
};