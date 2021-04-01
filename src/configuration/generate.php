<?php declare(strict_types=1);

namespace rikmeijer\Bootstrap\configuration;

use rikmeijer\Bootstrap\PHP;
use function Functional\partial_left;

return static function (string $from, string $namespace, bool $cached): void {
    $fopen = partial_left('\\fopen', $from . '.php');
    \rikmeijer\Bootstrap\resource\generate(static fn($pathVariable) => 'substr(__FILE__, 0, -4) . DIRECTORY_SEPARATOR . ' . $pathVariable . ', ' . PHP::export($cached), $fopen, $from, $namespace);
};