<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration {

    function open(string $resourcePath)
    {
        return \rikmeijer\Bootstrap\resource\open(substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . basename($resourcePath), false);
    }
}

namespace rikmeijer\Bootstrap\configuration {

    if (function_exists("rikmeijer\Bootstrap\configuration\generate") === false) {
        function generate(string $from, string $namespace, bool $cached): void
        {
            open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/configuration\\generate.php')(...func_get_args());
        }

    }
}

namespace rikmeijer\Bootstrap\configuration {

    if (function_exists("rikmeijer\Bootstrap\configuration\path") === false) {
        function path(): string
        {
            return open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/configuration\\path.php')(...func_get_args());
        }

    }
}

namespace rikmeijer\Bootstrap\configuration {

    if (function_exists("rikmeijer\Bootstrap\configuration\validate") === false) {
        function validate(array $config, callable $validator, string $property): mixed
        {
            return open('D:\\Rik Meijer\\git\\rikmeijer\\Bootstrap\\src/configuration\\validate.php')(...func_get_args());
        }

    }
}