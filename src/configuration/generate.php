<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\Configuration;

use rikmeijer\Bootstrap\PHP;

function generate(string $from, string $namespace, bool $cached): void
{
    $fp = fopen($from . '.php', 'wb');
    \rikmeijer\Bootstrap\resource\generate(static fn() => 'substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . basename($resourcePath), ' . PHP::export($cached), $fp, $from, $namespace);
    fclose($fp);
}