# Bootstrap

Bootstrap class for loading resources and configuration

## Usage

/bootstrap.php
```php
<?php
use rikmeijer\Bootstrap\Bootstrap;

// include composer autoloader
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
return new Bootstrap(__DIR__); // argument is configuration-path
```

Default configuration options: /config.defaults.php (/config.php is similar but can be gitignored and used for sensitive data)
```php
<?php
return [
    'logger' => [ // must be same as basename of resource loader
        'channel' => 'MyApp'
    ],
    'BOOTSTRAP' => [
        'path' => __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap' // optional: default is directory bootstrap under configuration-path
    ]
];
```

Resource loader: /bootstrap/logger.php
```php
<?php

use Psr\Log\LoggerInterface;

return function (\rikmeijer\Bootstrap\Bootstrap $bootstrap , array $configuration) : LoggerInterface {
    $logger = new Monolog\Logger($configuration['channel']);
    $logger->pushHandler(new \Monolog\Handler\SyslogHandler("debug"));
    return $logger;
};
```

/someother/file.php
```php
<?php

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';
$router = $bootstrap->resource('logger');
```
