<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\resource {

    function __open(string $resourcePath)
    {
        $resourcePath = str_replace("\\", chr(47), $resourcePath);
        static $closures = [];
        if (!isset($closures[$resourcePath])) {
            $closures[$resourcePath] = require substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . $resourcePath;
        }
        return $closures[$resourcePath];
    }
}

namespace rikmeijer\Bootstrap\resource {

    __open('/generate.php');
}

namespace rikmeijer\Bootstrap\resource {

    __open('/opener.php');
}