<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\Configuration;

return static function (): string {
    $configurationPath = getenv('BOOTSTRAP_CONFIGURATION_PATH');
    if (is_string($configurationPath) === false) {
        trigger_error('EnvVar BOOTSTRAP_CONFIGURATION_PATH not found', E_USER_ERROR);
    }
    return $configurationPath;
};