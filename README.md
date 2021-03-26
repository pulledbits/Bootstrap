# Bootstrap

Bootstraps closures with configuration and provides them as a PHP-function in your project. Making it easy to bootstrap
resources.

## Usage

Run vendor\bin\bootstrap to generate functions (configure file in config.php).

### /config.php

configuration options: /config.php (can be gitignored and used for sensitive data), defaults can be set as arguments in
the resource loader

```php
<?php /** @noinspection ALL */
return [
    'logger' => [ // must be same as basename of resource loader
        'channel' => 'OurApp'
    ],
    'BOOTSTRAP' => [
        'path' => CONFIGURATION_PATH . DIRECTORY_SEPARATOR . 'bootstrap' // optional: default is directory bootstrap under configuration-path
                                                                         // when no namespace is configured in resource
                                                                         // the function will be generated under
                                                                         // BOOTSTRAP.namespace . '\\' . basename(getcwd())
        'namespace' => 'my\\project\\namespace', // default rikmeijer\Bootstrap
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
use function rikmeijer\Bootstrap\configure;


return configure(static function(array $configuration, string $level, string $message) : LoggerInterface {
    $logger = new Monolog\Logger($configuration['channel']);
    $logger->pushHandler(new SyslogHandler("debug"));
    switch ($level) {
        case 'emergency':
            $logger->emergency($message);
            break;
    }
    return $logger;
}, [
    "channel" => \rikmeijer\Bootstrap\types\string("MyApp"), // helper functions reside in project namespace (BOOTSTRAP/namespace or \rikmeijer\Bootstrap\<BASENAME_CONFIG_DIR>)
    "no-default-option" => \rikmeijer\Bootstrap\types\string(null), // this will cause an error when not in config.php and thus enforcing a value (making it required)
    
    "simulation" => \rikmeijer\Bootstrap\types\boolean(true),
    "age" => \rikmeijer\Bootstrap\types\integer(1),
    "pi" => \rikmeijer\Bootstrap\types\float(3.14),
    "random-text" => \rikmeijer\Bootstrap\types\string("text"),
    "list-of-items" => \rikmeijer\Bootstrap\types\arr(["some", "value"])
]);
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

// include composer autoloader
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';   // or you can include this in composer.json:
                                                                    // "autoload" : {
                                                                    //      "files" : [
                                                                    //          "bootstrap.php"
                                                                    //      ]
                                                                    //  }
rikmeijer\Bootstrap\myProject\logger('emergency', "Houston, we have a problem.");
my\custom\namespace\loggerDependant();
```
