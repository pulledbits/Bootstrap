<?php

namespace pulledbits\Bootstrap;

final class Bootstrap
{
    private $configurationPath;

    public function __construct(string $configurationPath)
    {
        $this->configurationPath = $configurationPath;
    }

    public function resource(string $resource)
    {
        static $resources = [];
        if (array_key_exists($resource, $resources)) {
            return $resources[$resource];
        }
        $path = $this->config('BOOSTRAP')['path'];
        return $resources[$resource] = (require $path . DIRECTORY_SEPARATOR . $resource . '.php')($this);
    }

    public function config(string $section): array
    {
        static $config;
        if (isset($config) === false) {
            $defaults = (require $this->configurationPath . DIRECTORY_SEPARATOR . 'config.defaults.php');
            $environment = (require $this->configurationPath . DIRECTORY_SEPARATOR . 'config.php');
            $config = array_merge($defaults, $environment);
        }
        return $config[$section];
    }
}
