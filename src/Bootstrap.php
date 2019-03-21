<?php

namespace pulledbits\Bootstrap;

final class Bootstrap
{
    private $configurationPath;
    private $resources = [];
    private $config;

    public function __construct(string $configurationPath)
    {
        $this->configurationPath = $configurationPath;
        if (array_key_exists('preload', $this->config('BOOTSTRAP'))) {
            foreach ($this->config('BOOTSTRAP')['preload'] as $preloadAsset) {
                $this->resource($preloadAsset);
            }
        }
    }

    public function resource(string $resource)
    {
        if (array_key_exists($resource, $this->resources)) {
            return $this->resources[$resource];
        }
        $path = $this->config('BOOTSTRAP')['path'];
        return $this->resources[$resource] = (require $path . DIRECTORY_SEPARATOR . $resource . '.php')($this);
    }

    public function config(string $section): array
    {
        if (isset($this->config) === false) {
            $this->config = (include $this->configurationPath . DIRECTORY_SEPARATOR . 'config.defaults.php');
            if (file_exists($this->configurationPath . DIRECTORY_SEPARATOR . 'config.php')) {
                $this->config = array_merge($this->config, (include $this->configurationPath . DIRECTORY_SEPARATOR . 'config.php'));
            }
        }
        if (array_key_exists($section, $this->config) === false) {
            return [];
        }
        return $this->config[$section];
    }
}
