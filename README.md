# Bootstrap

Bootstrap Closure for loading resources and configuration

## Usage

Run vendor\bin\bootstrap to generate functions (configure file in config.php).

### /bootstrap.php

```php
<?php /** @noinspection ALL */
use rikmeijer\Bootstrap\Bootstrap;

// include composer autoloader
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
Bootstrap::initialize(__DIR__); // argument is configuration-path
```

### /config.php

Configuration options: /config.php (can be gitignored and used for sensitive data), defaults can be set as arguments in
the resource loader

```php
<?php /** @noinspection ALL */
return [
    'logger' => [ // must be same as basename of resource loader
        'channel' => 'OurApp'
    ],
    'BOOTSTRAP' => [
        'path' => CONFIGURATION_PATH . DIRECTORY_SEPARATOR . 'bootstrap' // optional: default is directory bootstrap under configuration-path
        'namespace' => __NAMESPACE__ . '\\' . basename(getcwd()), // eg rikmeijer\Bootstrap\myProject
        'functions-path' => CONFIGURATION_PATH . DIRECTORY_SEPARATOR . '_f.php'
    ]
];
```

### /bootstrap/logger.php

Resource loader for logger

```php
<?php /** @noinspection ALL */

use Monolog\Handler\SyslogHandler;
use Psr\Log\LoggerInterface;

$configuration = $validate([
    "channel" => \rikmeijer\Bootstrap\Configuration::default("MyApp")
]); 

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

### /bootstrap/loggerDependant.php

Other resource dependant of the logger resource, dependencies are automatically injected based on given (named)
attributes. Use of $configuration or $bootstrap is optional. Additional parameters can be passed to $bootstrap as
arguments

```php
<?php /** @noinspection ALL */

namespace my\custom\namespace;

use Psr\Log\LoggerInterface;
use \rikmeijer\Bootstrap\Dependency;

return static function() : LoggerInterface {
    rikmeijer\Bootstrap\myProject\logger('emergency', "Houston, we have a problem.");
};
```

### /public/index.php

```php
<?php /** @noinspection ALL */

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';
rikmeijer\Bootstrap\myProject\logger('emergency', "Houston, we have a problem.");
my\custom\namespace\loggerDependant();
```
