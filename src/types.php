<?php declare(strict_types=1);
namespace rikmeijer\Bootstrap\types {

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

namespace rikmeijer\Bootstrap\types {

    __open('/arr.php');
}

namespace rikmeijer\Bootstrap\types {

    if (function_exists("rikmeijer\Bootstrap\types\binary") === false) {
        function binary(array $defaultCommand = null)
        {
            return __open('/binary.php')(...func_get_args());
        }

    }
}

namespace rikmeijer\Bootstrap\types {

    __open('/boolean.php');
}

namespace rikmeijer\Bootstrap\types {

    __open('/file.php');
}

namespace rikmeijer\Bootstrap\types {

    __open('/float.php');
}

namespace rikmeijer\Bootstrap\types {

    __open('/integer.php');
}

namespace rikmeijer\Bootstrap\types {

    __open('/mixed.php');
}

namespace rikmeijer\Bootstrap\types {

    __open('/path.php');
}

namespace rikmeijer\Bootstrap\types {

    __open('/string.php');
}