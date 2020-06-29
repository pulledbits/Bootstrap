# Bootstrap

Bootstrap class for loading resources and configuration

## Usage

/bootstrap.php
```php
<?php
use rikmeijer\Bootstrap\Bootstrap;

// include composer autoloader
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
return new Bootstrap(__DIR__);
```

/config.defaults.php (/config.php is similar but can be gitignored and used for sensitive data)
```php
<?php
return [
    'BOOTSTRAP' => [
        'path' => __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap'
    ]
];
```

/bootstrap/logger.php
```php
<?php

use Psr\Log\LoggerInterface;

return function (\rikmeijer\Bootstrap\Bootstrap $bootstrap) : LoggerInterface {
    $logger = new Monolog\Logger("rikmeijer/Bootstrap");
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
