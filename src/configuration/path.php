<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

function path(): string
{
    $configurationPath = getenv('BOOTSTRAP_CONFIGURATION_PATH');
    if (is_string($configurationPath) === false) {
        $configurationPath = getcwd();
    }
    if (file_exists($configurationPath . DIRECTORY_SEPARATOR . 'config.php') === false) {
        trigger_error('Configuration path invalid: no config.php found', E_USER_ERROR);
    }
    return $configurationPath;
}