# Bootstrap

Bootstrap Closure for loading resources and configuration

## Usage

/bootstrap.php
```php
<?php
use rikmeijer\Bootstrap\Bootstrap;

// include composer autoloader
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
return Bootstrap::load(__DIR__); // argument is configuration-path
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

use Monolog\Handler\SyslogHandler;use Psr\Log\LoggerInterface;

return function (array $configuration) : LoggerInterface {
    $logger = new Monolog\Logger($configuration['channel']);
    $logger->pushHandler(new SyslogHandler("debug"));
    return $logger;
};
```

Other resource dependant of the logger resource, dependencies are automatically injected based on given (named)
attributes. Configuration parameter is optional.

```php
<?php

use Psr\Log\LoggerInterface;
use \rikmeijer\Bootstrap\Dependency;

return 
#[Dependency(loggerParameter: "logger")]
function (LoggerInterface $loggerParameter) : LoggerInterface {
    $loggerParameter->emergency("Houston, we have a problem.");
};
```

/someother/file.php

```php
<?php

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';
$logger = $bootstrap('logger');
```
