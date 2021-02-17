# Bootstrap

Bootstrap Closure for loading resources and configuration

## Usage

### /bootstrap.php

```php
<?php /** @noinspection ALL */
use rikmeijer\Bootstrap\Bootstrap;

// include composer autoloader
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
return Bootstrap::initialize(__DIR__); // argument is configuration-path
```

### /config.defaults.php
Default configuration options: /config.defaults.php (/config.php is similar but can be gitignored and used for sensitive data)
```php
<?php /** @noinspection ALL */
return [
    'logger' => [ // must be same as basename of resource loader
        'channel' => 'MyApp'
    ],
    'BOOTSTRAP' => [
        'path' => __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap' // optional: default is directory bootstrap under configuration-path
    ]
];
```

### /bootstrap/logger.php

Resource loader for logger

```php
<?php /** @noinspection ALL */

use Monolog\Handler\SyslogHandler;
use Psr\Log\LoggerInterface;

$configuration = $validate([]); 

return static function(string $level, string $message) use ($configuration) : LoggerInterface {
    $logger = new Monolog\Logger($configuration['channel']);
    $logger->pushHandler(new SyslogHandler("debug"));
    switch ($level) {
        case 'emergency':
            $logger->emergency($message);
            break;
    }
    return $logger;
};
```

### /bootstrap/logger-dependant.php

Other resource dependant of the logger resource, dependencies are automatically injected based on given (named)
attributes. Use of $configuration or $bootstrap is optional. Additional parameters can be passed to $bootstrap as
arguments

```php
<?php /** @noinspection ALL */

use Psr\Log\LoggerInterface;
use \rikmeijer\Bootstrap\Dependency;

return static function() use ($bootstrap) : LoggerInterface {
    $bootstrap('logger', 'emergency', "Houston, we have a problem.");
};
```

### /public/index.php
```php
<?php /** @noinspection ALL */

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';
$logger = $bootstrap('logger');
```
